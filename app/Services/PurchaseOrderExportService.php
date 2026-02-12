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

        // Basic PO Information - Fixed cell mappings
        $sheet->setCellValue('C4', $purchaseOrder->supplier->business_name ?? ''); // Supplier
        $sheet->setCellValue('C5', $purchaseOrder->supplier->address ?? ''); // Address
        $sheet->setCellValue('F4', $purchaseOrder->po_number); // PO Number
        $sheet->setCellValue('F5', $purchaseOrder->po_date->format('F d, Y')); // Date

        // TIN
        if ($purchaseOrder->tin) {
            $sheet->setCellValue('C6', $purchaseOrder->tin);
        } elseif ($purchaseOrder->supplier && $purchaseOrder->supplier->tin) {
            $sheet->setCellValue('C6', $purchaseOrder->supplier->tin);
        }

        // Supplier Name Override
        if ($purchaseOrder->supplier_name_override) {
            $sheet->setCellValue('C4', $purchaseOrder->supplier_name_override);
        }

        // Items table - Starting from row 14
        $startRow = 14;
        $items = $purchaseOrder->quotation
            ? $purchaseOrder->quotation->quotationItems
            : $purchaseOrder->purchaseRequest->items;

        $rowIndex = $startRow;
        foreach ($items as $item) {
            // Unit (Column C)
            $unit = $item->unit_of_measure ?? ($item->purchaseRequestItem->unit_of_measure ?? 'pcs');
            $sheet->setCellValue('C'.$rowIndex, $unit);

            // Description (Column D)
            $description = $item->item_name ?? ($item->purchaseRequestItem->item_name ?? '');
            $sheet->setCellValue('D'.$rowIndex, $description);

            // Quantity (Column E)
            $quantity = $item->quantity ?? ($item->quantity_requested ?? 0);
            $sheet->setCellValue('E'.$rowIndex, $quantity);

            // Unit Cost (Column F)
            $unitCost = $item->unit_price ?? ($item->estimated_unit_cost ?? 0);
            $sheet->setCellValue('F'.$rowIndex, $unitCost);

            // Amount (Column G)
            $amount = $item->total_price ?? ($item->estimated_total_cost ?? 0);
            $sheet->setCellValue('G'.$rowIndex, $amount);

            $rowIndex++;
        }

        // Total Amount in Words (D44)
        $totalInWords = $this->convertNumberToWords($purchaseOrder->total_amount);
        $sheet->setCellValue('D44', $totalInWords);

        // Total Amount (G44)
        $sheet->setCellValue('G44', $purchaseOrder->total_amount);

        // Signatories
        $ceo = PoSignatory::active()->position('ceo')->first();
        $chiefAccountant = PoSignatory::active()->position('chief_accountant')->first();

        if ($ceo) {
            // CEO at E51 - ALL CAPS except suffix from DB
            $formattedCeoName = $this->formatSignatoryName($ceo);
            $sheet->setCellValue('E51', $formattedCeoName);
        }

        if ($chiefAccountant) {
            // Chief Accountant at C61 - ALL CAPS except suffix from DB
            $formattedAccountantName = $this->formatSignatoryName($chiefAccountant);
            $sheet->setCellValue('C61', $formattedAccountantName);
        }
    }

    /**
     * Format signatory name for export: name and prefix in ALL CAPS, suffix as-is from DB.
     */
    protected function formatSignatoryName(PoSignatory $signatory): string
    {
        // Build the part to uppercase (prefix + display_name)
        $namePart = $signatory->prefix
            ? trim($signatory->prefix.' '.$signatory->display_name)
            : $signatory->display_name;

        // Uppercase the name part
        $result = strtoupper($namePart);

        // Append suffix as-is if present
        if ($signatory->suffix) {
            $result .= ', '.$signatory->suffix;
        }

        return $result;
    }

    /**
     * Convert number to words (Philippine Peso format).
     * Uses pure PHP so it works without the intl extension (e.g. on Windows/XAMPP).
     */
    protected function convertNumberToWords(float $amount): string
    {
        $pesos = (int) floor($amount);
        $centavos = (int) round(($amount - $pesos) * 100);

        // Handle float rounding that can produce 100 centavos (e.g. 1.999999 -> 200).
        if ($centavos === 100) {
            $pesos++;
            $centavos = 0;
        }

        $words = ucwords($this->integerToWords($pesos)).' Pesos';

        return $words.' & '.str_pad((string) $centavos, 2, '0', STR_PAD_LEFT).'/100';
    }

    /**
     * Convert a non-negative integer to words (no intl dependency).
     */
    protected function integerToWords(int $n): string
    {
        if ($n === 0) {
            return 'zero';
        }

        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        $thousands = ['', 'thousand', 'million', 'billion'];

        $chunks = [];
        $chunkIndex = 0;
        while ($n > 0) {
            $chunk = $n % 1000;
            $n = (int) floor($n / 1000);
            if ($chunk > 0) {
                $chunkWords = $this->formatHundreds($chunk, $ones, $tens);
                $chunks[] = $chunkWords.($thousands[$chunkIndex] ? ' '.$thousands[$chunkIndex] : '');
            }
            $chunkIndex++;
        }

        return implode(' ', array_reverse($chunks));
    }

    /**
     * Format a number from 1-999 into words.
     *
     * @param  array<string>  $ones
     * @param  array<string>  $tens
     */
    protected function formatHundreds(int $n, array $ones, array $tens): string
    {
        if ($n === 0) {
            return '';
        }
        if ($n < 20) {
            return $ones[$n];
        }
        if ($n < 100) {
            $word = $tens[(int) floor($n / 10)];
            $remainder = $n % 10;

            return $remainder > 0 ? $word.' '.$ones[$remainder] : $word;
        }
        $word = $ones[(int) floor($n / 100)].' hundred';
        $remainder = $n % 100;

        return $remainder > 0 ? $word.' '.$this->formatHundreds($remainder, $ones, $tens) : $word;
    }
}
