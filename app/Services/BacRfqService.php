<?php

namespace App\Services;

use App\Models\Document;
use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use App\Models\RfqGeneration;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class BacRfqService
{
    private PhpWord $phpWord;

    private $section;

    private PurchaseRequest $purchaseRequest;

    private array $data;

    /**
     * Generate RFQ document for a Purchase Request
     *
     * @param  array|null  $signatoryData  Array of signatory data (position, name, prefix, suffix)
     */
    public function generateRfq(PurchaseRequest $purchaseRequest, ?array $signatoryData = null): ?Document
    {
        $this->purchaseRequest = $purchaseRequest;
        $this->loadData($signatoryData);
        $this->initializeDocument();
        $this->buildDocument();

        // Save to storage and create document record
        $filename = $this->saveToStorage();

        if (! $filename) {
            return null;
        }

        return $this->attachToDocuments($filename);
    }

    /**
     * Generate RFQ document for a specific item group
     *
     * @param  PrItemGroup  $itemGroup  The item group to generate RFQ for
     * @param  array|null  $signatoryData  Array of signatory data (position, name, prefix, suffix)
     */
    public function generateRfqForGroup(PrItemGroup $itemGroup, ?array $signatoryData = null): ?RfqGeneration
    {
        $itemGroup->load(['purchaseRequest.resolutionSignatories', 'items']);
        $this->purchaseRequest = $itemGroup->purchaseRequest;

        // Load data specific to this group
        $this->loadDataForGroup($itemGroup, $signatoryData);
        $this->initializeDocument();
        $this->buildDocument();

        // Save to storage
        $filename = $this->saveToStorageForGroup($itemGroup);

        if (! $filename) {
            return null;
        }

        // Create RFQ generation record
        return $this->createRfqGenerationRecord($itemGroup, $filename, $signatoryData);
    }

    /**
     * Load data for a specific item group
     */
    private function loadDataForGroup(PrItemGroup $itemGroup, ?array $signatoryData = null): void
    {
        // Get resolution date for deadline calculation
        $resolutionDoc = $this->purchaseRequest->documents()
            ->where('document_type', 'bac_resolution')
            ->latest()
            ->first();

        $resolutionDate = $resolutionDoc ? $resolutionDoc->created_at->format('Y-m-d') : now()->format('Y-m-d');

        // Generate RFQ number for this group
        $rfqNumber = RfqGeneration::generateNextRfqNumber();

        // Load signatories data
        $signatories = $this->loadSignatoriesForGroup($itemGroup, $signatoryData);

        $this->data = [
            'rfq_no' => $rfqNumber,
            'group_name' => $itemGroup->group_name,
            'group_code' => $itemGroup->group_code,
            'resolution_no' => $this->purchaseRequest->resolution_number ?? 'N/A',
            'resolution_date' => $resolutionDate,
            'procurement_type' => $this->purchaseRequest->procurement_method ?? 'small_value_procurement',
            'bac_chairperson' => $signatories['bac_chairperson']['name'] ?? 'N/A',
            'purpose' => $this->purchaseRequest->purpose ?? 'N/A',
            'canvasser' => $signatories['canvassing_officer']['name'] ?? 'N/A',
            'deadline_date' => $this->calculateDeadlineDate($resolutionDate),
            'items' => $itemGroup->items->toArray(),
            'signatories' => $signatories,
        ];
    }

    /**
     * Load signatories for group (similar to existing loadSignatories)
     */
    private function loadSignatoriesForGroup(PrItemGroup $itemGroup, ?array $signatories = null): array
    {
        $defaultSignatories = [
            'bac_chairperson' => ['name' => 'Christopher R. Garingan', 'prefix' => null, 'suffix' => null],
            'canvassing_officer' => ['name' => 'Chito D. Temporal', 'prefix' => null, 'suffix' => null],
        ];

        // If signatories parameter is provided, use it
        if ($signatories) {
            return array_merge($defaultSignatories, $signatories);
        }

        // Check if RFQ generation already exists with signatories
        $rfqGeneration = $itemGroup->rfqGeneration;
        if ($rfqGeneration && $rfqGeneration->rfqSignatories && $rfqGeneration->rfqSignatories->isNotEmpty()) {
            $result = [];
            foreach ($rfqGeneration->rfqSignatories as $sig) {
                $result[$sig->position] = [
                    'name' => $sig->full_name,
                    'prefix' => $sig->prefix,
                    'suffix' => $sig->suffix,
                ];
            }

            return array_merge($defaultSignatories, $result);
        }

        // Try to load from BAC Signatories setup (auto-apply from configuration)
        $signatoryLoader = new SignatoryLoaderService;
        $requiredPositions = ['bac_chairperson', 'canvassing_officer'];
        $bacSignatories = $signatoryLoader->loadActiveSignatories($requiredPositions, false);

        if (! empty($bacSignatories)) {
            return array_merge($defaultSignatories, $bacSignatories);
        }

        // Fall back to default signatories
        return $defaultSignatories;
    }

    /**
     * Save RFQ document to storage for a specific group
     */
    private function saveToStorageForGroup(PrItemGroup $itemGroup): ?string
    {
        try {
            $filename = 'RFQ_'.$this->data['rfq_no'].'_'.$itemGroup->group_code.'_'.now()->format('Ymd_His').'.docx';
            $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
            $tempPath = storage_path('app/temp_'.$filename);
            $writer->save($tempPath);

            Storage::disk('local')->put('rfq/'.$filename, file_get_contents($tempPath));
            unlink($tempPath);

            return $filename;
        } catch (\Exception $e) {
            Log::error('Failed to save RFQ document for group: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Create RFQ generation record and save signatories
     */
    private function createRfqGenerationRecord(PrItemGroup $itemGroup, string $filename, ?array $signatoryData = null): RfqGeneration
    {
        $rfqGeneration = RfqGeneration::create([
            'pr_item_group_id' => $itemGroup->id,
            'rfq_number' => $this->data['rfq_no'],
            'generated_by' => Auth::id() ?? 1,
            'generated_at' => now(),
            'file_path' => 'rfq/'.$filename,
        ]);

        // Save signatories if provided
        if ($signatoryData) {
            foreach ($signatoryData as $position => $data) {
                \App\Models\RfqSignatory::create([
                    'rfq_generation_id' => $rfqGeneration->id,
                    'position' => $position,
                    'user_id' => $data['user_id'] ?? null,
                    'name' => $data['name'] ?? null,
                    'prefix' => $data['prefix'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                ]);
            }
        }

        return $rfqGeneration;
    }

    /**
     * Load data from Purchase Request
     */
    private function loadData(?array $signatoryData = null): void
    {
        $this->purchaseRequest->load(['items', 'resolutionSignatories', 'rfqSignatories']);

        // Get resolution date for deadline calculation
        $resolutionDoc = $this->purchaseRequest->documents()
            ->where('document_type', 'bac_resolution')
            ->latest()
            ->first();

        $resolutionDate = $resolutionDoc ? $resolutionDoc->created_at->format('Y-m-d') : now()->format('Y-m-d');

        // Load signatories data - either from parameter or from database
        $signatories = $this->loadSignatories($signatoryData);

        $this->data = [
            'rfq_no' => $this->purchaseRequest->rfq_number,
            'resolution_no' => $this->purchaseRequest->resolution_number ?? 'N/A',
            'resolution_date' => $resolutionDate,
            'procurement_type' => $this->purchaseRequest->procurement_method ?? 'small_value_procurement',
            'bac_chairperson' => $signatories['bac_chairperson']['name'] ?? 'N/A',
            'purpose' => $this->purchaseRequest->purpose ?? 'N/A',
            'canvasser' => $signatories['canvassing_officer']['name'] ?? 'N/A',
            'deadline_date' => $this->calculateDeadlineDate($resolutionDate),
            'items' => $this->purchaseRequest->items->toArray(),
            'signatories' => $signatories,
        ];
    }

    /**
     * Load signatories from parameter or database, with fallback to defaults
     */
    private function loadSignatories(?array $signatories = null): array
    {
        $defaultSignatories = [
            'bac_chairperson' => ['name' => '', 'prefix' => null, 'suffix' => null],
            'canvassing_officer' => ['name' => '', 'prefix' => null, 'suffix' => null],
        ];

        // If signatories parameter is provided, use it (for regeneration overrides)
        if ($signatories) {
            return array_merge($defaultSignatories, $signatories);
        }

        // Try to load from rfq_signatories table (per-document overrides)
        if ($this->purchaseRequest->rfqSignatories && $this->purchaseRequest->rfqSignatories->isNotEmpty()) {
            $result = [];
            foreach ($this->purchaseRequest->rfqSignatories as $sig) {
                $result[$sig->position] = [
                    'name' => $sig->display_name,
                    'prefix' => $sig->prefix,
                    'suffix' => $sig->suffix,
                ];
            }

            Log::info('Loaded RFQ signatories from DB', [
                'count' => count($result),
                'positions' => array_keys($result),
            ]);

            return array_merge($defaultSignatories, $result);
        }

        // Try to load from BAC Signatories setup (auto-apply from configuration)
        $signatoryLoader = new SignatoryLoaderService;
        $requiredPositions = ['bac_chairperson', 'canvassing_officer'];
        $bacSignatories = $signatoryLoader->loadActiveSignatories($requiredPositions, false);

        if (! empty($bacSignatories)) {
            Log::info('Loaded RFQ signatories from BAC Signatories setup', [
                'count' => count($bacSignatories),
                'positions' => array_keys($bacSignatories),
            ]);

            return array_merge($defaultSignatories, $bacSignatories);
        }

        // Fall back to hardcoded defaults
        Log::info('Using hardcoded default signatories for RFQ');

        return $defaultSignatories;
    }

    /**
     * Get procurement method header text
     */
    private function getProcurementHeaderText(string $procurementType): string
    {
        return match ($procurementType) {
            'small_value_procurement' => 'For Small Value Procurement under Sec. 53.9 of the Revised IRR of R.A. 9184',
            'negotiated_procurement' => 'Negotiated Procurement (Agency-to-Agency) Under Section 53.5',
            'direct_contracting' => 'For Direct Contracting under Sec. 50 of the Revised IRR of R.A. 9184',
            'public_bidding' => 'For Public Bidding under the Revised IRR of R.A. 9184',
            default => '',
        };
    }

    /**
     * Calculate deadline date (resolution date + 4 days)
     */
    private function calculateDeadlineDate(string $resolutionDate): string
    {
        try {
            $date = new DateTime($resolutionDate);
            $date->modify('+4 days');

            return $date->format('F j'); // Format as "November 15"
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Initialize PHPWord document
     */
    private function initializeDocument(): void
    {
        $this->phpWord = new PhpWord;
        $this->phpWord->setDefaultFontName('Century Gothic');
        $this->phpWord->setDefaultFontSize(10);

        $this->defineStyles();
        $this->setDocumentProperties();
        $this->section = $this->createSection();
        $this->addHeader();
    }

    /**
     * Define paragraph and font styles
     */
    private function defineStyles(): void
    {
        $this->phpWord->addParagraphStyle('CenterHeader', [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 0,
        ]);

        $this->phpWord->addFontStyle('HeaderFont', [
            'name' => 'Times New Roman',
            'bold' => true,
            'size' => 9,
        ]);

        $this->phpWord->addFontStyle('CenterBold', [
            'bold' => true,
            'allCaps' => true,
        ]);

        $this->phpWord->addFontStyle('UnderlineBold', [
            'bold' => true,
            'allCaps' => true,
            'underline' => 'single',
        ]);

        $this->phpWord->addFontStyle('ConditionsText', [
            'name' => 'Times New Roman',
            'size' => 6,
        ]);

        $this->phpWord->addParagraphStyle('ConditionsList', [
            'spaceAfter' => 0,
            'spaceBefore' => 0,
            'indent' => 1,
        ]);
    }

    /**
     * Set document properties
     */
    private function setDocumentProperties(): void
    {
        $properties = $this->phpWord->getDocInfo();
        $properties->setCreator('Cagayan State University - BAC System');
        $properties->setTitle('Request for Quotation Document');
        $properties->setSubject('Request for Quotation - RFQ No. '.$this->data['rfq_no']);
    }

    /**
     * Create document section with page settings
     */
    private function createSection()
    {
        return $this->phpWord->addSection([
            'pageSizeW' => Converter::inchToTwip(8.5),
            'pageSizeH' => Converter::inchToTwip(13),
            'marginTop' => Converter::cmToTwip(1),
            'marginBottom' => Converter::cmToTwip(1),
            'marginLeft' => Converter::cmToTwip(1),
            'marginRight' => Converter::cmToTwip(1),
        ]);
    }

    /**
     * Add header with logo and text
     */
    private function addHeader(): void
    {
        $header = $this->section->addHeader();
        $header->addText('BIDS AND AWARDS COMMITTEE', 'HeaderFont', 'CenterHeader');
        $header->addText('Sanchez Mira, Cagayan', ['size' => 7, 'name' => 'Times New Roman', 'bold' => true], 'CenterHeader');
        $header->addText('REQUEST FOR QUOTATION', ['size' => 8, 'name' => 'Times New Roman', 'bold' => true], 'CenterHeader');
        $header->addText($this->getProcurementHeaderText($this->data['procurement_type']), ['size' => 6, 'italic' => true, 'name' => 'Times New Roman'], 'CenterHeader');

        // Add logo
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            $header->addImage(
                $logoPath,
                [
                    'positioning' => 'absolute',
                    'wrappingStyle' => 'infront',
                    'width' => Converter::cmToPixel(1.5 * 0.75),
                    'height' => Converter::cmToPixel(1.5 * 0.75),
                    'posHorizontal' => 'absolute',
                    'posVertical' => 'absolute',
                    'marginLeft' => Converter::cmToPixel(3 * 0.75),
                    'marginTop' => Converter::cmToPixel(-1.5 * 0.75),
                ]
            );
        }
    }

    /**
     * Build the complete document
     */
    private function buildDocument(): void
    {
        $this->buildSupplierAddressSection();
        $this->buildInvitationText();
        $this->buildConditions();
        $this->buildBacChairpersonSignature();
        $this->buildPurposeSection();
        $this->buildItemsTable();
        $this->buildFooterSignatures();
    }

    /**
     * Build supplier address section with RFQ number
     */
    private function buildSupplierAddressSection(): void
    {
        $table = $this->section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'alignment' => JcTable::CENTER,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow();
        $cell1 = $table->addCell(65 * 50);
        $cell1->addText('_______________________________', ['name' => 'Times New Roman', 'size' => 8], ['spaceAfter' => 0]);
        $cell1->addText('_______________________________', ['name' => 'Times New Roman', 'size' => 8], ['spaceAfter' => 0]);
        $cell1->addText('_______________________________', ['name' => 'Times New Roman', 'size' => 8], ['spaceAfter' => 0]);

        $cell2 = $table->addCell(35 * 50);
        $cell2->addText('RFQ NO: '.$this->data['rfq_no'], ['name' => 'Times New Roman', 'size' => 8], ['spaceAfter' => 0]);
    }

    /**
     * Build invitation text with deadline
     */
    private function buildInvitationText(): void
    {
        $run = $this->section->addTextRun([
            'alignment' => Jc::START,
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ]);

        $run->addText('ㅤ', ['size' => 6]);
        $run->addTextBreak();
        $run->addText('The ', 'ConditionsText');
        $run->addText('Cagayan State University ', ['bold' => true, 'name' => 'Times New Roman', 'size' => 6]);
        $run->addText('is pleased to invite you to quote your lowest price on the item/s listed below,', 'ConditionsText');
        $run->addTextBreak();
        $run->addText('stating the shortest time of delivery and submit your quotation duly signed by you, or by your authorized', 'ConditionsText');
        $run->addTextBreak();
        $run->addText('representative not later than ', 'ConditionsText', ['spaceAfter' => 0, 'spaceBefore' => 0]);
        $run->addText($this->data['deadline_date'], ['underline' => 'single', 'name' => 'Times New Roman', 'size' => 6], ['spaceAfter' => 0, 'spaceBefore' => 0]);
        $run->addText(' in a sealed envelope.', 'ConditionsText', ['spaceAfter' => 0, 'spaceBefore' => 0]);
    }

    /**
     * Build conditions section
     */
    private function buildConditions(): void
    {
        $this->section->addText('CONDITIONS:', 'ConditionsText', ['spaceAfter' => 0, 'spaceBefore' => 0]);
        $this->section->addText('1. Quotation IS INCLUSIVE of TAX &amp; Delivery Cost;', 'ConditionsText', 'ConditionsList');
        $this->section->addText('2. Warranty shall be for a period of six months for supplies and materials:', 'ConditionsText', 'ConditionsList');
        $this->section->addText('ㅤone year for equipment from date of acceptance by the procuring entity.', 'ConditionsText', 'ConditionsList');
        $this->section->addText('3. Registration with the CSU Registry of Suppliers and Contractors shall be required prior to issuance of', 'ConditionsText', 'ConditionsList');
        $this->section->addText('ㅤNotice of Award/ Purchase Order.', 'ConditionsText', 'ConditionsList');
        $this->section->addText('4. Price validity shall be for a period of 10 calendar days;', 'ConditionsText', 'ConditionsList');
        $this->section->addText('5. All Procurement shall be delivered first before payment.', 'ConditionsText', 'ConditionsList');
        $this->section->addText('6. Winning bidders shall be on a per item/lot basis.', 'ConditionsText', 'ConditionsList');
    }

    /**
     * Build BAC Chairperson signature section
     */
    private function buildBacChairpersonSignature(): void
    {
        $table = $this->section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'alignment' => JcTable::CENTER,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow();
        $table->addCell(60 * 50);
        $cell2 = $table->addCell(40 * 50);
        $cell2->addText($this->data['bac_chairperson'], ['name' => 'Times New Roman', 'size' => 9, 'allCaps' => true, 'bold' => true], ['spaceAfter' => 0, 'alignment' => Jc::CENTER]);
        $cell2->addText('BAC Chairperson', ['name' => 'Times New Roman', 'size' => 9], ['spaceAfter' => 0, 'alignment' => Jc::CENTER]);
    }

    /**
     * Build purpose section
     */
    private function buildPurposeSection(): void
    {
        $this->section->addText('ㅤ', ['size' => 7], ['spaceAfter' => 0, 'spaceBefore' => 0]);

        $table = $this->section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'alignment' => JcTable::START,
        ]);

        $table->addRow();
        $table->addCell(
            Converter::cmToTwip(2.1),
            ['valign' => 'center']
        )->addText('PURPOSE:', ['size' => 6, 'name' => 'Times New Roman'], ['alignment' => Jc::CENTER]);

        $table->addCell(
            Converter::cmToTwip(15),
            ['valign' => 'center']
        )->addText($this->data['purpose'], ['allCaps' => true, 'bold' => true], ['spaceAfter' => 0, 'spaceBefore' => 0]);

        $this->section->addText('ㅤ', ['size' => 1], ['spaceAfter' => 0, 'spaceBefore' => 0]);
    }

    /**
     * Build items table (minimum 20 rows)
     */
    private function buildItemsTable(): void
    {
        $table = $this->section->addTable([
            'borderSize' => 1,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'alignment' => JcTable::CENTER,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        // Header row
        $table->addRow();
        $headerStyle = ['valign' => 'center'];

        $table->addCell(Converter::cmToTwip(0.6 * 0.75), $headerStyle)->addText('Item No.', ['size' => 5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $table->addCell(Converter::cmToTwip(1.5 * 0.75), $headerStyle)->addText('Unit', ['size' => 7], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $table->addCell(null, $headerStyle)->addText('Description', ['size' => 7], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $table->addCell(Converter::cmToTwip(2.2 * 0.75), $headerStyle)->addText('Qty', ['size' => 7], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $table->addCell(Converter::cmToTwip(2.5 * 0.75), $headerStyle)->addText('Unit Cost', ['size' => 7], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $table->addCell(Converter::cmToTwip(2.5 * 0.75), $headerStyle)->addText('Total Cost', ['size' => 7], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);

        // Determine total rows (minimum 20)
        $minRows = 20;
        $itemCount = count($this->data['items']);
        $totalRows = max($minRows, $itemCount);

        // Data rows
        $cellStyle = ['valign' => 'center'];
        $paragraphCenter = ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0];
        $paragraphLeft = ['spaceAfter' => 0, 'spaceBefore' => 0];

        for ($i = 0; $i < $totalRows; $i++) {
            $table->addRow();

            if ($i < $itemCount) {
                $item = $this->data['items'][$i];
                $itemNumber = $i + 1;

                $table->addCell(Converter::cmToTwip(0.6), $cellStyle)->addText((string) $itemNumber, [], $paragraphCenter);
                $table->addCell(Converter::cmToTwip(1.5), $cellStyle)->addText($item['unit_of_measure'] ?? '', ['allCaps' => true], $paragraphCenter);
                $table->addCell(null, $cellStyle)->addText($item['item_name'] ?? '', ['allCaps' => true], $paragraphLeft);
                $table->addCell(Converter::cmToTwip(2.2), $cellStyle)->addText((string) ($item['quantity_requested'] ?? ''), [], $paragraphCenter);
                $table->addCell(Converter::cmToTwip(2.5), $cellStyle)->addText('', [], $paragraphLeft);
                $table->addCell(Converter::cmToTwip(2.5), $cellStyle)->addText('', [], $paragraphLeft);
            } else {
                // Empty rows
                $table->addCell(Converter::cmToTwip(0.6), $cellStyle)->addText('', [], $paragraphCenter);
                $table->addCell(Converter::cmToTwip(1.5), $cellStyle)->addText('', [], $paragraphCenter);
                $table->addCell(null, $cellStyle)->addText('', [], $paragraphLeft);
                $table->addCell(Converter::cmToTwip(2.2), $cellStyle)->addText('', [], $paragraphCenter);
                $table->addCell(Converter::cmToTwip(2.5), $cellStyle)->addText('', [], $paragraphLeft);
                $table->addCell(Converter::cmToTwip(2.5), $cellStyle)->addText('', [], $paragraphLeft);
            }
        }

        $this->section->addText('ㅤ', ['size' => 5], ['spaceAfter' => 0, 'spaceBefore' => 0]);
    }

    /**
     * Build footer with canvasser and supplier signature sections
     */
    private function buildFooterSignatures(): void
    {
        $table = $this->section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'alignment' => JcTable::CENTER,
            'cellMargin' => 50,
            'width' => 100 * 50,
            'unit' => 'pct',
            'valign' => 'center',
        ]);

        $table->addRow();
        $cell1 = $table->addCell(Converter::cmToTwip(10 * 0.75), ['valign' => 'center']);
        $cell1->addText($this->data['canvasser'], ['name' => 'Times New Roman', 'size' => 9, 'allCaps' => true, 'bold' => true], ['spaceAfter' => 0, 'spaceBefore' => 0, 'alignment' => Jc::CENTER]);
        $cell1->addText('Canvasser:', ['name' => 'Times New Roman', 'size' => 6], ['spaceBefore' => 0, 'alignment' => Jc::CENTER]);
        $cell1->addText('Resolution No.: '.$this->data['resolution_no'], ['name' => 'Times New Roman', 'size' => 9, 'allCaps' => true, 'underline' => 'single', 'bold' => true], ['spaceAfter' => 0, 'spaceBefore' => 0, 'alignment' => Jc::START]);

        $table->addCell(null);

        $cell3 = $table->addCell(Converter::cmToTwip(7.2));
        $cell3->addText('________________________________________', [], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cell3->addText('Printed Name and Signature/Date:', ['name' => 'Times New Roman', 'size' => 6], ['spaceAfter' => 0, 'spaceBefore' => 0, 'alignment' => Jc::CENTER]);
        $cell3->addText('________________________________________', [], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $cell3->addText('Contact Number:', ['name' => 'Times New Roman', 'size' => 6], ['spaceAfter' => 0, 'spaceBefore' => 0, 'alignment' => Jc::CENTER]);
    }

    /**
     * Save document to storage
     */
    private function saveToStorage(): ?string
    {
        try {
            $filename = $this->data['rfq_no'].'.docx';
            $tempPath = storage_path('app/temp/'.$filename);

            // Ensure temp directory exists
            if (! is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0777, true);
            }

            // Save to temp location
            $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
            $writer->save($tempPath);

            if (! file_exists($tempPath)) {
                return null;
            }

            // Move to rfq directory
            $finalPath = 'rfq/'.$filename;
            Storage::put($finalPath, file_get_contents($tempPath));

            // Clean up temp file
            @unlink($tempPath);

            return $filename;
        } catch (\Exception $e) {
            Log::error('Failed to generate RFQ: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Attach document to purchase request
     */
    private function attachToDocuments(string $filename): ?Document
    {
        try {
            // Check if RFQ already exists, update it instead
            $existingDoc = Document::where('documentable_type', PurchaseRequest::class)
                ->where('documentable_id', $this->purchaseRequest->id)
                ->where('document_type', 'bac_rfq')
                ->first();

            if ($existingDoc) {
                // Update existing document
                $existingDoc->update([
                    'document_number' => 'RFQ-'.now()->format('Y-m-d-His'),
                    'title' => 'Request for Quotation - '.$this->data['rfq_no'],
                    'file_name' => $filename,
                    'file_path' => 'rfq/'.$filename,
                    'file_extension' => 'docx',
                    'file_size' => Storage::size('rfq/'.$filename),
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'version' => $existingDoc->version + 1,
                    'status' => 'approved',
                ]);

                return $existingDoc;
            }

            // Create new document record
            return Document::create([
                'document_number' => 'RFQ-'.now()->format('Y-m-d-His'),
                'documentable_type' => PurchaseRequest::class,
                'documentable_id' => $this->purchaseRequest->id,
                'document_type' => 'bac_rfq',
                'title' => 'Request for Quotation - '.$this->data['rfq_no'],
                'description' => 'Request for Quotation for Purchase Request '.$this->purchaseRequest->pr_number,
                'file_name' => $filename,
                'file_path' => 'rfq/'.$filename,
                'file_extension' => 'docx',
                'file_size' => Storage::size('rfq/'.$filename),
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'version' => 1,
                'is_current_version' => true,
                'uploaded_by' => Auth::id() ?? 1,
                'is_public' => false,
                'visible_to_roles' => json_encode(['BAC Secretariat', 'CEO', 'Supplier']),
                'status' => 'approved',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to attach RFQ document: '.$e->getMessage());

            return null;
        }
    }
}
