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
        $sheet = $spreadsheet->getActiveSheet();

        $this->fillPrData($sheet, $purchaseRequest);

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
    protected function fillPrData($sheet, PurchaseRequest $purchaseRequest): void
    {
        $purchaseRequest->loadMissing(['requester', 'items.lotChildren']);

        // Header: PR number and date
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

        // Fill items starting at row 11
        $startRow = 11;
        $maxRow = 55;
        $row = $startRow;
        $stockNo = 1;

        $items = $purchaseRequest->items->filter(fn ($i) => ! $i->isLotChild());

        foreach ($items as $item) {
            if ($row > $maxRow) {
                break;
            }

            if ($item->isLotHeader()) {
                // Lot header row: stock no. (has unit), unit=lot, description=LOT NAME (uppercase), qty=1, unit cost=total; bold only description cell
                $sheet->setCellValue('A'.$row, $stockNo);
                $sheet->setCellValue('B'.$row, 'lot');
                $sheet->setCellValue('C'.$row, strtoupper($item->lot_name ?? $item->item_name));
                $sheet->setCellValue('E'.$row, 1);
                $sheet->setCellValue('F'.$row, $item->estimated_unit_cost);
                $sheet->getStyle('C'.$row)->getFont()->setBold(true);
                $row++;
                $stockNo++;

                // Lot child sub-rows: no stock no. (no unit), description only; leave E/F unset so cells stay blank and template total formula still evaluates (empty = 0)
                foreach ($item->lotChildren as $child) {
                    if ($row > $maxRow) {
                        break;
                    }
                    $sheet->setCellValue('A'.$row, '');
                    $sheet->setCellValue('B'.$row, '');
                    $sheet->setCellValue('C'.$row, $child->quantity_requested.' '.$child->unit_of_measure.', '.$child->item_name);
                    $row++;
                }
                // Blank row after each lot for readability
                if ($row <= $maxRow) {
                    $row++;
                }
            } else {
                // Standalone item: stock no. (has unit)
                $sheet->setCellValue('A'.$row, $stockNo);
                $sheet->setCellValue('B'.$row, $item->unit_of_measure ?? '');
                $sheet->setCellValue('C'.$row, $item->item_name ?? '');
                $sheet->setCellValue('E'.$row, $item->quantity_requested ?? 0);
                $sheet->setCellValue('F'.$row, $item->estimated_unit_cost ?? 0);
                $row++;
                $stockNo++;
            }
        }
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
