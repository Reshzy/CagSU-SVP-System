<?php

namespace App\Services;

use App\Models\PoSignatory;
use App\Models\PurchaseOrder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurchaseOrderExportService
{
    /**
     * Generate Excel file from PO data using template
     */
    public function generateExcel(PurchaseOrder $purchaseOrder): string
    {
        $templatePath = storage_path('app/templates/PurchaseOrderTemplate.xlsx');

        if (! file_exists($templatePath)) {
            throw new \Exception('Purchase Order template not found');
        }

        // Load template
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Fill in PO data
        $this->fillPoData($sheet, $purchaseOrder);

        // Generate temporary file
        $tempFile = storage_path('app/temp/PO-'.$purchaseOrder->po_number.'-'.time().'.xlsx');

        // Create temp directory if it doesn't exist
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Fill spreadsheet with PO data
     */
    protected function fillPoData($sheet, PurchaseOrder $purchaseOrder): void
    {
        // Load relationships
        $purchaseOrder->load(['supplier', 'purchaseRequest.items', 'quotation.quotationItems']);

        // Basic PO Information
        $sheet->setCellValue('B5', $purchaseOrder->supplier->business_name ?? '');
        $sheet->setCellValue('B6', $purchaseOrder->supplier->address ?? '');
        $sheet->setCellValue('E5', $purchaseOrder->po_number);
        $sheet->setCellValue('E6', $purchaseOrder->po_date->format('F d, Y'));

        // TIN
        if ($purchaseOrder->tin) {
            $sheet->setCellValue('B7', $purchaseOrder->tin);
        } elseif ($purchaseOrder->supplier && $purchaseOrder->supplier->tin) {
            $sheet->setCellValue('B7', $purchaseOrder->supplier->tin);
        }

        // Supplier Name Override
        if ($purchaseOrder->supplier_name_override) {
            $sheet->setCellValue('B8', $purchaseOrder->supplier_name_override);
        }

        // Items table (starting from row 10, adjust based on actual template)
        $startRow = 10;
        $items = $purchaseOrder->quotation
            ? $purchaseOrder->quotation->quotationItems
            : $purchaseOrder->purchaseRequest->items;

        $rowIndex = $startRow;
        foreach ($items as $index => $item) {
            // Item number
            $sheet->setCellValue('A'.$rowIndex, $index + 1);

            // Unit
            $unit = $item->unit_of_measure ?? ($item->purchaseRequestItem->unit_of_measure ?? 'pcs');
            $sheet->setCellValue('B'.$rowIndex, $unit);

            // Item Description
            $description = $item->item_name ?? ($item->purchaseRequestItem->item_name ?? '');
            $sheet->setCellValue('C'.$rowIndex, $description);

            // Quantity
            $quantity = $item->quantity ?? ($item->quantity_requested ?? 0);
            $sheet->setCellValue('D'.$rowIndex, $quantity);

            // Unit Cost
            $unitCost = $item->unit_price ?? ($item->estimated_unit_cost ?? 0);
            $sheet->setCellValue('E'.$rowIndex, $unitCost);

            // Amount
            $amount = $item->total_price ?? ($item->estimated_total_cost ?? 0);
            $sheet->setCellValue('F'.$rowIndex, $amount);

            $rowIndex++;
        }

        // Total Amount
        $sheet->setCellValue('F'.($rowIndex + 1), $purchaseOrder->total_amount);

        // Financial Details
        $sheet->setCellValue('B'.($rowIndex + 3), $purchaseOrder->funds_cluster ?? '');
        $sheet->setCellValue('B'.($rowIndex + 4), $purchaseOrder->funds_available ?? '');
        $sheet->setCellValue('B'.($rowIndex + 5), $purchaseOrder->ors_burs_no ?? '');
        $sheet->setCellValue('B'.($rowIndex + 6), $purchaseOrder->ors_burs_date ? $purchaseOrder->ors_burs_date->format('F d, Y') : '');

        // Signatories
        $ceo = PoSignatory::active()->position('ceo')->first();
        $chiefAccountant = PoSignatory::active()->position('chief_accountant')->first();

        if ($ceo) {
            $sheet->setCellValue('B'.($rowIndex + 8), $ceo->full_name);
            $sheet->setCellValue('B'.($rowIndex + 9), $ceo->position_name);
        }

        if ($chiefAccountant) {
            $sheet->setCellValue('E'.($rowIndex + 8), $chiefAccountant->full_name);
            $sheet->setCellValue('E'.($rowIndex + 9), $chiefAccountant->position_name);
        }

        // Delivery Details
        $sheet->setCellValue('B'.($rowIndex + 11), $purchaseOrder->delivery_address);
        $sheet->setCellValue('B'.($rowIndex + 12), $purchaseOrder->delivery_date_required->format('F d, Y'));
    }
}
