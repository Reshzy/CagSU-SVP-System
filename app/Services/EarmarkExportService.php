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

        // B27: Date/Time Printed
        $sheet->setCellValue('B27', $datePrinted->format('F j, Y g:i A'));

        // A19+/C19+: Object of Expenditures table rows
        $this->fillItemsTable($sheet, $purchaseRequest);
    }

    /**
     * Fill the Object of Expenditures table starting at row 19.
     * A column: item name (Object of Expenditures)
     * C column: amount
     */
    protected function fillItemsTable($sheet, PurchaseRequest $purchaseRequest): void
    {
        $row = 19;

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
