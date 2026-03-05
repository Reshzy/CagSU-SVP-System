<?php

namespace App\Services;

use App\Models\PoSignatory;
use App\Models\PurchaseRequest;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurchaseRequestExportService
{
    /**
     * Generate Excel file from PR data using the official template.
     * Returns the path to the temporary file.
     */
    public function generateExcel(PurchaseRequest $purchaseRequest): string
    {
        $templatePath = storage_path('app/templates/PurchaseRequestTemplate.xlsx');

        if (! file_exists($templatePath)) {
            throw new \Exception('Purchase Request template not found at: '.$templatePath);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $templateSheet = $spreadsheet->getActiveSheet();

        // Create the first working sheet as a clone of the template,
        // then remove the original template sheet from the workbook so there
        // are no duplicate sheet titles. Keep the detached template sheet
        // instance for cloning additional pages later.
        $firstSheet = clone $templateSheet;
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($templateSheet));
        $firstSheet->setTitle($templateSheet->getTitle());
        $spreadsheet->addSheet($firstSheet, 0);
        $spreadsheet->setActiveSheetIndex(0);

        $this->fillPrData($spreadsheet, $firstSheet, $templateSheet, $purchaseRequest);

        $tempDir = storage_path('app/temp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir.'/PR-'.$purchaseRequest->pr_number.'-'.time().'.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Fill spreadsheet with PR data.
     */
    protected function fillPrData($spreadsheet, $sheet, $templateSheet, PurchaseRequest $purchaseRequest): void
    {
        $purchaseRequest->loadMissing(['requester', 'items.lotChildren']);

        // Fill header / metadata on the first page
        $this->fillHeaderAndMetadata($sheet, $purchaseRequest);

        // Items / lots are rendered starting at row 11 up to row 55 on each page
        $startRow = 11;
        $maxRow = 55;
        $rowsPerPage = $maxRow - $startRow + 1;

        // Build a flat list of display rows (lot headers, children, blanks, standalone items)
        $items = $purchaseRequest->items->filter(fn ($i) => ! $i->isLotChild());

        $displayRows = [];
        $stockNo = 1;

        foreach ($items as $item) {
            if ($item->isLotHeader()) {
                $displayRows[] = [
                    'type' => 'lot_header',
                    'item' => $item,
                    'stock_no' => $stockNo,
                ];
                $stockNo++;

                foreach ($item->lotChildren as $child) {
                    $displayRows[] = [
                        'type' => 'lot_child',
                        'child' => $child,
                    ];
                }

                // Blank row after each lot for readability
                $displayRows[] = [
                    'type' => 'blank',
                ];
            } else {
                $displayRows[] = [
                    'type' => 'standalone',
                    'item' => $item,
                    'stock_no' => $stockNo,
                ];
                $stockNo++;
            }
        }

        $totalRows = count($displayRows);
        if ($totalRows === 0) {
            // No items; nothing to paginate, no page numbers needed.
            $sheet->setCellValue('C56', '');

            return;
        }

        $pageSheets = [$sheet];
        $totalPages = (int) ceil($totalRows / $rowsPerPage);

        // Render each page into its own sheet
        for ($pageIndex = 0; $pageIndex < $totalPages; $pageIndex++) {
            if ($pageIndex === 0) {
                $pageSheet = $sheet;
            } else {
                $pageSheet = $this->cloneTemplateSheet($spreadsheet, $templateSheet, $pageIndex);
                $this->fillHeaderAndMetadata($pageSheet, $purchaseRequest);
                $pageSheets[] = $pageSheet;
            }

            $row = $startRow;
            $startIndex = $pageIndex * $rowsPerPage;
            $endIndex = min($startIndex + $rowsPerPage, $totalRows);

            for ($i = $startIndex; $i < $endIndex; $i++, $row++) {
                $rowDef = $displayRows[$i];

                if ($rowDef['type'] === 'lot_header') {
                    /** @var \App\Models\PurchaseRequestItem $lotItem */
                    $lotItem = $rowDef['item'];
                    $pageSheet->setCellValue('A'.$row, $rowDef['stock_no']);
                    $pageSheet->setCellValue('B'.$row, 'lot');
                    $pageSheet->setCellValue('C'.$row, strtoupper($lotItem->lot_name ?? $lotItem->item_name));
                    $pageSheet->setCellValue('E'.$row, 1);
                    $pageSheet->setCellValue('F'.$row, $lotItem->estimated_unit_cost);
                    $pageSheet->getStyle('C'.$row)->getFont()->setBold(true);
                } elseif ($rowDef['type'] === 'lot_child') {
                    /** @var \App\Models\PurchaseRequestItem $child */
                    $child = $rowDef['child'];
                    $pageSheet->setCellValue('A'.$row, '');
                    $pageSheet->setCellValue('B'.$row, '');
                    $pageSheet->setCellValue('C'.$row, $child->quantity_requested.' '.$child->unit_of_measure.', '.$child->item_name);
                } elseif ($rowDef['type'] === 'standalone') {
                    /** @var \App\Models\PurchaseRequestItem $standalone */
                    $standalone = $rowDef['item'];
                    $pageSheet->setCellValue('A'.$row, $rowDef['stock_no']);
                    $pageSheet->setCellValue('B'.$row, $standalone->unit_of_measure ?? '');
                    $pageSheet->setCellValue('C'.$row, $standalone->item_name ?? '');
                    $pageSheet->setCellValue('E'.$row, $standalone->quantity_requested ?? 0);
                    $pageSheet->setCellValue('F'.$row, $standalone->estimated_unit_cost ?? 0);
                } elseif ($rowDef['type'] === 'blank') {
                    // Intentionally leave this row completely untouched so that
                    // all cells (including the quantity / unit cost cells used
                    // by the template's total-cost formula) remain truly empty.
                    // This avoids Excel interpreting empty strings as text and
                    // producing #VALUE! in the total column.
                }
            }
        }

        // Page numbering in C56: only when there are 2+ pages
        if ($totalPages === 1) {
            $sheet->setCellValue('C56', '');
        } else {
            foreach ($pageSheets as $index => $pageSheet) {
                $pageNumber = $index + 1;
                $pageSheet->setCellValue('C56', 'Page no. '.$pageNumber.' of '.$totalPages);
            }
        }
    }

    /**
     * Write header / metadata cells (PR number, date, purpose, requester, CEO) to the given sheet.
     */
    protected function fillHeaderAndMetadata($sheet, PurchaseRequest $purchaseRequest): void
    {
        // PR number and date
        $sheet->setCellValue('D7', $purchaseRequest->pr_number ?? '');
        $sheet->setCellValue(
            'F7',
            $purchaseRequest->created_at
                ? 'Date: '.$purchaseRequest->created_at->format('m.d.Y')
                : ''
        );

        // Purpose
        $sheet->setCellValue('B58', $purchaseRequest->purpose ?? '');

        // Requester info
        $requester = $purchaseRequest->requester;
        if ($requester) {
            $sheet->setCellValue('B66', strtoupper($requester->name));
            $sheet->setCellValue('B67', $requester->position ?? $requester->designation ?? '');
        }

        // CEO signatory
        $ceo = PoSignatory::active()->position('ceo')->first();
        if ($ceo) {
            $sheet->setCellValue('E66', $this->formatSignatoryName($ceo));
        }
    }

    /**
     * Clone the template sheet to create a new page sheet in the workbook.
     */
    protected function cloneTemplateSheet($spreadsheet, $templateSheet, int $pageIndex)
    {
        $newSheet = clone $templateSheet;
        $newSheet->setTitle('PR Page '.($pageIndex + 1));
        $spreadsheet->addSheet($newSheet);

        return $newSheet;
    }

    /**
     * Format signatory name: name and prefix in ALL CAPS, suffix as-is.
     */
    protected function formatSignatoryName(PoSignatory $signatory): string
    {
        $namePart = $signatory->prefix
            ? trim($signatory->prefix.' '.$signatory->display_name)
            : $signatory->display_name;

        $result = strtoupper($namePart);

        if ($signatory->suffix) {
            $result .= ', '.$signatory->suffix;
        }

        return $result;
    }
}
