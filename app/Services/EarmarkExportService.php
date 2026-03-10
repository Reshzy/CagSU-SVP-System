<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EarmarkExportService
{
    /**
     * Generate an Excel file from earmark data using the official template.
     * Returns the path to the temporary file.
     */
    public function generateExcel(PurchaseRequest $purchaseRequest): string
    {
        $templatePath = storage_path('app/templates/EarmarkTemplate.xlsx');

        if (! file_exists($templatePath)) {
            throw new \Exception('Earmark template not found at: '.$templatePath);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $purchaseRequest->loadMissing(['requester', 'items', 'department']);

        $this->fillEarmarkData($sheet, $purchaseRequest);

        $tempDir = storage_path('app/temp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir.'/EARMARK-'.$purchaseRequest->earmark_id.'-'.time().'.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Fill all template cells with PR/earmark data.
     */
    protected function fillEarmarkData($sheet, PurchaseRequest $purchaseRequest): void
    {
        $datePrinted = now();

        // A6: Earmark number — insert after existing prefix text
        $earmarkNo = $purchaseRequest->earmark_id ?? '';
        $sheet->setCellValue('A6', 'EARMARK NO. '.$earmarkNo);

        // A7: Dated — "Dated: {date_printed} - {date_to}"
        $datedValue = 'Dated: ';
        $datedValue .= $datePrinted->format('F j, Y');
        if ($purchaseRequest->earmark_date_to) {
            $datedValue .= ' - '.$purchaseRequest->earmark_date_to->format('F j, Y');
        }
        $sheet->setCellValue('A7', $datedValue);

        // B10: Fund (funding_source)
        $sheet->setCellValue('B10', $purchaseRequest->funding_source ?? '');

        // B11: Legal Basis
        $sheet->setCellValue('B11', $purchaseRequest->legal_basis ?? '');

        // B12: Requested By (requester name)
        $sheet->setCellValue('B12', $purchaseRequest->requester?->name ?? '');

        // B13: Remarks
        $sheet->setCellValue('B13', $purchaseRequest->current_step_notes ?? '');

        // A16: Programs / Projects / Activities (insert after existing prefix)
        $programsValue = 'Programs / Projects / Activities :    '
            .($purchaseRequest->earmark_programs_activities ?? '');
        $sheet->setCellValue('A16', $programsValue);

        // A17: Responsibility Center (insert after existing prefix)
        $rcValue = '                  Responsibility Center :   '
            .($purchaseRequest->earmark_responsibility_center ?? '');
        $sheet->setCellValue('A17', $rcValue);

        // A18:C18 header and A19+/C19+: Object of Expenditures table rows
        $baseHeaderRow = 18;
        $baseDataRow = 19;
        $objectRowCount = $this->fillObjectOfExpendituresTable($sheet, $purchaseRequest, $baseHeaderRow, $baseDataRow);

        $extraObjectRows = max(0, $objectRowCount - 1);

        // Date/Time Printed (originally B27) shifts down with inserted object rows
        $dateTimeRow = 27 + $extraObjectRows;
        $sheet->setCellValue('B'.$dateTimeRow, $datePrinted->format('F j, Y g:i A'));

        // PR items can be rendered in a separate section if needed, away from A19/C19
        $itemsStartRow = $dateTimeRow + 2;
        $this->fillItemsTable($sheet, $purchaseRequest, $itemsStartRow);
    }

    /**
     * Fill the Object of Expenditures table starting at given base rows.
     *
     * A column: combined code + description, e.g. \"(50213040-02). R & M School Buildings\"
     * C column: amount (per row). If all amounts are null and at least one row exists,
     *           C19 will fall back to the approved budget total.
     *
     * @return int Number of object rows rendered.
     */
    protected function fillObjectOfExpendituresTable($sheet, PurchaseRequest $purchaseRequest, int $baseHeaderRow, int $baseDataRow): int
    {
        $rows = $purchaseRequest->earmark_object_expenditures ?? [];

        if (! is_array($rows) || count($rows) === 0) {
            // No explicit rows; if we have an approved budget total, put it in C19 as a fallback.
            if ($purchaseRequest->estimated_total !== null) {
                $sheet->setCellValue('C'.$baseDataRow, $purchaseRequest->estimated_total);
            }

            return 0;
        }

        $rowCount = count($rows);
        $extraRows = max(0, $rowCount - 1);

        if ($extraRows > 0) {
            // Insert new rows below the first data row to accommodate additional object rows
            $sheet->insertNewRowBefore($baseDataRow + 1, $extraRows);

            // Copy styles from the original row across the inserted block (A..C)
            $sourceRange = 'A'.$baseDataRow.':C'.$baseDataRow;
            $targetRange = 'A'.$baseDataRow.':C'.($baseDataRow + $extraRows);
            $sheet->duplicateStyle($sheet->getStyle($sourceRange), $targetRange);
        }

        $hasAnyAmount = false;

        foreach (array_values($rows) as $index => $row) {
            $targetRow = $baseDataRow + $index;

            $code = isset($row['code']) && $row['code'] !== null ? trim((string) $row['code']) : '';
            $description = isset($row['description']) && $row['description'] !== null ? trim((string) $row['description']) : '';
            $amount = $row['amount'] ?? null;

            if ($code === '' && $description === '' && ($amount === null || $amount === '')) {
                continue;
            }

            $label = '';
            if ($code !== '') {
                $label = $code;
                if ($description !== '') {
                    $label .= '. '.$description;
                }
            } else {
                $label = $description;
            }

            $sheet->setCellValue('A'.$targetRow, $label);

            if ($amount !== null && $amount !== '') {
                $sheet->setCellValue('C'.$targetRow, (float) $amount);
                $hasAnyAmount = true;
            }
        }

        // If no per-row amounts were set but we do have a total, fall back to putting it on the first data row (C19).
        if (! $hasAnyAmount && $purchaseRequest->estimated_total !== null) {
            $sheet->setCellValue('C'.$baseDataRow, $purchaseRequest->estimated_total);
        }

        return $rowCount;
    }

    /**
     * Fill PR items in a separate section of the sheet (for reference only).
     */
    protected function fillItemsTable($sheet, PurchaseRequest $purchaseRequest, int $startRow): void
    {
        $row = $startRow;

        foreach ($purchaseRequest->items as $item) {
            if ($item->parent_lot_id !== null) {
                continue;
            }

            $sheet->setCellValue('A'.$row, $item->item_name ?? '');
            $sheet->setCellValue('C'.$row, $item->estimated_total_cost ?? ($item->estimated_unit_cost * $item->quantity_requested));

            $row++;
        }
    }
}
