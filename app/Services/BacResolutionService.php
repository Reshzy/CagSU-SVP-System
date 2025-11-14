<?php

namespace App\Services;

use App\Models\Document;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use NumberToWords\NumberToWords;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class BacResolutionService
{
    private PhpWord $phpWord;
    private $section;
    private PurchaseRequest $purchaseRequest;
    private array $data;

    /**
     * Generate BAC Resolution document for a Purchase Request
     */
    public function generateResolution(PurchaseRequest $purchaseRequest): ?Document
    {
        $this->purchaseRequest = $purchaseRequest;
        $this->loadData();
        $this->initializeDocument();
        $this->buildDocument();

        // Save to storage and create document record
        $filename = $this->saveToStorage();
        
        if (!$filename) {
            return null;
        }

        return $this->attachToDocuments($filename);
    }

    /**
     * Load data from Purchase Request
     */
    private function loadData(): void
    {
        $this->purchaseRequest->load(['requester', 'department']);

        // Get CEO who approved (from ceo_initial_approval workflow)
        $ceoApproval = $this->purchaseRequest->workflowApprovals()
            ->where('step_name', 'ceo_initial_approval')
            ->where('status', 'approved')
            ->first();

        $ceoName = 'Dr. Urdujah G. Alvarado';
        if ($ceoApproval && $ceoApproval->approvedBy) {
            $ceoName = $ceoApproval->approvedBy->name;
        }

        $this->data = [
            'resolution_no' => $this->purchaseRequest->resolution_number,
            'purchase_request_no' => $this->purchaseRequest->pr_number,
            'approved_by' => $ceoName,
            'requested_by' => $this->purchaseRequest->requester->name ?? 'N/A',
            'budget' => number_format($this->purchaseRequest->estimated_total, 2),
            'purpose' => $this->purchaseRequest->purpose ?? 'N/A',
            'mode_of_procurement' => $this->getProcurementMethodName(),
        ];
    }

    /**
     * Get human-readable procurement method name
     */
    private function getProcurementMethodName(): string
    {
        $method = $this->purchaseRequest->procurement_method ?? 'small_value_procurement';

        $names = [
            'small_value_procurement' => 'Small Value Procurement',
            'public_bidding' => 'Public Bidding',
            'direct_contracting' => 'Direct Contracting',
            'negotiated_procurement' => 'Negotiated Procurement',
        ];

        return $names[$method] ?? 'Small Value Procurement';
    }

    /**
     * Initialize PHPWord document
     */
    private function initializeDocument(): void
    {
        $this->phpWord = new PhpWord();
        $this->phpWord->setDefaultFontName('Montserrat');
        $this->phpWord->setDefaultFontSize(9);

        $this->defineStyles();
        $this->setDocumentProperties();
        $this->section = $this->createSection();
        $this->addHeaderFooter();
    }

    /**
     * Define paragraph and font styles
     */
    private function defineStyles(): void
    {
        $this->phpWord->addParagraphStyle('CenterHeader', ['alignment' => Jc::CENTER]);
        $this->phpWord->addFontStyle('CenterBold', ['bold' => true, 'allCaps' => true]);
        $this->phpWord->addFontStyle('UnderlineBold', ['bold' => true, 'allCaps' => true, 'underline' => 'single']);
        $this->phpWord->addFontStyle('UnderlineBoldSmall', ['bold' => true, 'underline' => 'single']);
    }

    /**
     * Set document properties
     */
    private function setDocumentProperties(): void
    {
        $properties = $this->phpWord->getDocInfo();
        $properties->setCreator('Cagayan State University - BAC System');
        $properties->setTitle('BAC Resolution Document');
        $properties->setSubject('Resolution No. ' . $this->data['resolution_no']);
    }

    /**
     * Create document section with page settings
     */
    private function createSection()
    {
        return $this->phpWord->addSection([
            'pageSizeW' => Converter::inchToTwip(8.5),
            'pageSizeH' => Converter::inchToTwip(13),
            'marginTop' => Converter::inchToTwip(1.5),
            'marginBottom' => Converter::inchToTwip(1.5),
            'marginLeft' => Converter::inchToTwip(1),
            'marginRight' => Converter::inchToTwip(1),
        ]);
    }

    /**
     * Add header and footer images
     */
    private function addHeaderFooter(): void
    {
        $imageConfig = [
            'width' => Converter::inchToPixel(6.5 * 0.75),
            'height' => Converter::inchToPixel(1 * 0.75),
            'posHorizontal' => 'center',
            'posHorizontalRel' => 'page',
        ];

        $headerPath = public_path('images/header.png');
        $footerPath = public_path('images/footer.png');

        // Footer - only add if image exists
        if (file_exists($footerPath)) {
            $footer = $this->section->addFooter();
            $footer->addImage($footerPath, array_merge($imageConfig, [
                'posVertical' => 'bottom',
                'posVerticalRel' => 'page',
            ]));
        }

        // Header - only add if image exists
        if (file_exists($headerPath)) {
            $header = $this->section->addHeader();
            $header->addImage($headerPath, array_merge($imageConfig, [
                'posVertical' => 'top',
                'posVerticalRel' => 'page',
            ]));
        }
    }

    /**
     * Build the complete document
     */
    private function buildDocument(): void
    {
        $this->buildPageOne();
        $this->section->addPageBreak();
        $this->buildPageTwo();
    }

    /**
     * Build page one content
     */
    private function buildPageOne(): void
    {
        $this->addResolutionHeading();
        $this->addWhereasClauses();
        $this->addNowThereforeClause();
        $this->addResolvedFurtherClause();
    }

    /**
     * Build page two content
     */
    private function buildPageTwo(): void
    {
        $this->addPageTwoHeader();
        $this->section->addTextBreak(2);
        $this->addResolvedDate();
        $this->addBACSignatures();
        $this->addCertification();
        $this->addSecretariatSignature();
        $this->addApproval();
    }

    /**
     * Add resolution heading
     */
    private function addResolutionHeading(): void
    {
        // Resolution Number
        $run = $this->section->addTextRun('CenterHeader');
        $run->addText('RESOLUTION NO. ', 'CenterBold');
        $run->addText($this->data['resolution_no'], [
            'bold' => true,
            'size' => 9,
            'allCaps' => true,
            'underline' => 'single',
        ]);

        // Title
        $run = $this->section->addTextRun('CenterHeader');
        $run->addText(
            'A RESOLUTION RECOMMENDING SMALL VALUE PROCUREMENT UNDER SECTION NO.53.9, ' .
            'OF THE 2016 REVISED IMPLEMENTING RULES AND REGULATIONS OF R.A. 9184,',
            'CenterBold'
        );
        $run->addTextBreak();
        $run->addText('For Purchase Request Number ', 'CenterBold');
        $run->addText($this->data['purchase_request_no'], [
            'bold' => true,
            'size' => 9,
            'allCaps' => true,
            'underline' => 'single',
        ]);
    }

    /**
     * Add WHEREAS clauses
     */
    private function addWhereasClauses(): void
    {
        // Clause 1: Procurement Act
        $this->addTextRun(function ($run) {
            $run->addText(
                'WHEREAS, it is the function of the Bids and Awards Committee to assist and to ensure ' .
                'that the university abides by the standards set forth by Republic Act No. 9184, otherwise known as '
            );
            $run->addText('"The Government Procurement Reform Act"', ['italic' => true]);
            $run->addText(' (as amended) and its Implementing Rules and Regulations, to all its procurement activities;');
        });

        // Clause 2: Purchase Request Details
        $this->addTextRun(function ($run) {
            $run->addText('WHEREAS, items covered by ');
            $run->addText('Purchase Request No. ', ['bold' => true]);
            $run->addText($this->data['purchase_request_no'], 'UnderlineBold');
            $run->addText(' duly approved by ');
            $run->addText($this->data['approved_by'], 'CenterBold');
            $run->addText(', requested by ');
            $run->addText($this->data['requested_by'], 'CenterBold');
            $run->addText(' has an Approved Budget for the Contract (ABC) in the amount of ');
            $run->addText($this->convertBudgetToWords(), 'CenterBold');
            $run->addText(', ' . $this->data['purpose'] . '.', 'CenterBold');
        });

        // Clause 3: BAC Recognition
        $this->addJustifiedText(
            'WHEREAS, the Bids and Awards Committee recognizes the intent of the Procurement law ' .
            'that all procurement activities of the government must redound to its benefit;'
        );

        // Clause 4: Alternative Methods
        $this->addJustifiedText(
            'WHEREAS, under certain circumstances the law provides exemption to Public Bidding, ' .
            'as it also provides for Alternative Methods of procurement, particularly identified in ' .
            'Section 53.9 of the IRR, TO WIT:'
        );
        $this->addTextRun(function ($run) {
            $run->addText($this->data['mode_of_procurement'] . '.', ['bold' => true]);
            $run->addText(
                ' Where the procurement does not fall under Shopping in Section 52 of this IRR and ' .
                'the amount involved does not exceed the thresholds prescribed in Annex "H" of this IRR.',
                ['italic' => true]
            );
        }, ['indent' => 1]);

        // Clause 5: Shopping Definition
        $this->section->addText(
            'WHEREAS, Section 52.1(b) of IRR identifies the goods that could be produced through Shopping:',
            [],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 0]
        );
        $this->section->addText(
            'Procurement of Ordinary or Regular office supplies and equipment not available in the ' .
            'thresholds prescribed in Annex "H" of this IRR.',
            ['italic' => true],
            ['alignment' => Jc::BOTH, 'indent' => 1, 'spaceBefore' => 0]
        );

        // Clause 6: Office Supplies Definition
        $this->section->addText(
            'WHEREAS, Section 52.2 of the IRR defines the phrase "ordinary or regular office supplies", to wit:',
            [],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 0]
        );
        $this->addTextRun(function ($run) {
            $run->addText(
                '"Ordinary or regular office supplies" shall be understood to include those supplies, ' .
                'commodities, or materials which, depending on the procuring transaction of its official ' .
                'businesses, and consumed in the day-to-day operations of said procuring entity. ',
                ['italic' => true]
            );
            $run->addText(
                'However, office supplies shall not include services such as repair and maintenance of ' .
                'equipment and furniture, as well as trucking, hauling, janitorial, security, and related ' .
                'or analogous services.'
            );
        }, ['indent' => 1, 'spaceBefore' => 0]);

        // Clause 7: Threshold Amount
        $this->addJustifiedText(
            'WHEREAS, the thresholds prescribed in Annex "H"V-D-8-a-i (For NGAs, GOCCs, GFIs, SUCs, ' .
            'and Autonomous Regional Government, One Million Pesos) of the IRR of R.A. 9184 is One ' .
            'Million Pesos (PhP1,000,000.00);'
        );

        // Clause 8: Compliance
        $this->addJustifiedText(
            'WHEREAS, the present procurement does not fall under Shopping in Section 52 of the ' .
            'Revised IRR of the R.A. 9184 and the amount involved does not exceed the thresholds ' .
            'prescribed in Annex "H" of the said IRR.'
        );
    }

    /**
     * Add NOW THEREFORE clause
     */
    private function addNowThereforeClause(): void
    {
        $this->addTextRun(function ($run) {
            $run->addText(
                'NOW, THEREFORE, we, the Members of the Bids and Awards Committee, hereby RESOLVE as it ' .
                'is hereby RESOLVED, to recommend '
            );
            $run->addText($this->data['mode_of_procurement'], ['bold' => true, 'allCaps' => true]);
            $run->addText(' as mode of procurement for each component of ');
            $run->addText(
                'Purchase Request Number ' . $this->data['purchase_request_no'],
                ['bold' => true]
            );
        });
    }

    /**
     * Add RESOLVED FURTHER clause
     */
    private function addResolvedFurtherClause(): void
    {
        $this->addJustifiedText(
            'RESOLVED FURTHER that a copy of this Resolution be forwarded to the BAC Secretariat with ' .
            'a specific instruction to float request for quotation as required by the procurement law, ' .
            'especially Section 54.2 of the Revised IRR (at least three (3) quotations)'
        );
    }

    /**
     * Add page two header
     */
    private function addPageTwoHeader(): void
    {
        $run = $this->section->addTextRun('CenterHeader');
        $run->addText('RESOLUTION NO. ', 'CenterBold');
        $run->addText($this->data['resolution_no'], [
            'bold' => true,
            'size' => 9,
            'allCaps' => true,
            'underline' => 'single',
        ]);
    }

    /**
     * Add resolved date
     */
    private function addResolvedDate(): void
    {
        $date = $this->getCurrentDate();

        $this->addTextRun(function ($run) use ($date) {
            $run->addText('RESOLVED this ');
            $run->addText($date['day'], 'UnderlineBoldSmall');
            $run->addText($date['suffix'], ['bold' => true, 'underline' => 'single', 'superScript' => true]);
            $run->addText(' day of ');
            $run->addText($date['month'] . ', ' . $date['year'], 'UnderlineBoldSmall');
            $run->addText(' at Cagayan State University – Sanchez Mira Campus, Sanchez Mira, Cagayan.');
        });
    }

    /**
     * Add BAC signatures
     */
    private function addBACSignatures(): void
    {
        $table = $this->createSignatureTable();

        $signatures = [
            [['name' => 'Christopher R. Garingan', 'title' => 'BAC Chairman', 'span' => 2]],
            [
                ['name' => 'ATTY. Jan Leandro P. Verzon', 'title' => 'BAC Vice Chairman'],
                ['name' => 'Melvin S. Atayan', 'title' => 'BAC Member']
            ],
            [
                ['name' => 'Valentin M. Apostol', 'title' => 'BAC Member'],
                ['name' => 'Chris Ian T. Rodriguez', 'title' => 'BAC Member']
            ],
        ];

        foreach ($signatures as $row) {
            $this->addSignatureRow($table, $row);
        }
    }

    /**
     * Add certification text
     */
    private function addCertification(): void
    {
        $this->section->addTextBreak(2);
        $this->addJustifiedText(
            'I HEREBY CERTIFY that the foregoing is a true and correct copy of the Resolution regularly ' .
            'presented to and adopted by the Bids and Awards Committee and that the signatures set above ' .
            'the prospective names of the Committee members are their true and genuine signatures.'
        );
    }

    /**
     * Add secretariat signature
     */
    private function addSecretariatSignature(): void
    {
        $table = $this->createSignatureTable();
        $this->addSignatureRow($table, [
            ['name' => 'Chanda T. Aquino', 'title' => 'Head, BAC Secretariat', 'span' => 2]
        ]);
    }

    /**
     * Add approval section
     */
    private function addApproval(): void
    {
        $this->section->addTextBreak(2);
        $this->section->addText('APPROVED:');

        $run = $this->section->addTextRun();
        $run->addTextBreak();
        $run->addText($this->data['approved_by'] . ', ', ['bold' => true, 'allCaps' => true]);
        $run->addText('Ph.D.', ['bold' => true]);
        $run->addTextBreak();
        $run->addText('Campus Executive Officer');
        $run->addTextBreak();
        $run->addText('Cagayan State University – Sanchez Mira Campus');
        $run->addTextBreak(2);
        $run->addText('Date: _____________________');
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Add justified text paragraph
     */
    private function addJustifiedText(string $text): void
    {
        $this->section->addText($text, [], ['alignment' => Jc::BOTH]);
    }

    /**
     * Add text run with callback
     */
    private function addTextRun(callable $callback, array $paragraphStyle = []): void
    {
        $defaultStyle = ['alignment' => Jc::BOTH];
        $run = $this->section->addTextRun(array_merge($defaultStyle, $paragraphStyle));
        $callback($run);
    }

    /**
     * Convert budget to words
     */
    private function convertBudgetToWords(): string
    {
        $budget = floatval(str_replace([',', ' '], '', $this->data['budget']));
        $amount = number_format($budget, 2, '.', '');
        [$whole, $fraction] = explode('.', $amount);

        $numberToWords = new NumberToWords();
        $transformer = $numberToWords->getNumberTransformer('en');

        $words = ucfirst($transformer->toWords((int) $whole)) . ' pesos';

        if ((int) $fraction > 0) {
            $words .= ' and ' . $transformer->toWords((int) $fraction) . ' centavos';
        }

        return $words . ' (PHP ' . number_format($budget, 2) . ')';
    }

    /**
     * Get current date details
     */
    private function getCurrentDate(): array
    {
        $day = date('j');
        $suffix = $this->getOrdinalSuffix($day);

        return [
            'day' => $day,
            'suffix' => $suffix,
            'month' => date('F'),
            'year' => date('Y'),
        ];
    }

    /**
     * Get ordinal suffix for day
     */
    private function getOrdinalSuffix(int $day): string
    {
        if (!in_array(($day % 100), [11, 12, 13])) {
            switch ($day % 10) {
                case 1:
                    return 'st';
                case 2:
                    return 'nd';
                case 3:
                    return 'rd';
            }
        }
        return 'th';
    }

    /**
     * Create signature table
     */
    private function createSignatureTable()
    {
        return $this->section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'alignment' => JcTable::CENTER,
        ]);
    }

    /**
     * Add signature row to table
     */
    private function addSignatureRow($table, array $signatures): void
    {
        $table->addRow();

        foreach ($signatures as $sig) {
            $cellStyle = [
                'alignment' => Jc::CENTER,
                'valign' => 'bottom',
                'cellMarginTop' => 300,
                'cellMarginBottom' => 300,
            ];

            $cell = $table->addCell(isset($sig['span']) ? 9000 : 4500, $cellStyle);

            if (isset($sig['span'])) {
                $cell->getStyle()->setGridSpan($sig['span']);
            }

            $cell->addTextBreak(2);
            $cell->addText($sig['name'], 'CenterBold', ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $cell->addText($sig['title'], null, ['alignment' => Jc::CENTER, 'spaceBefore' => 0]);
        }
    }

    /**
     * Save document to storage
     */
    private function saveToStorage(): ?string
    {
        try {
            $filename = $this->data['resolution_no'] . '.docx';
            $tempPath = storage_path('app/temp/' . $filename);

            // Ensure temp directory exists
            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0777, true);
            }

            // Save to temp location
            $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
            $writer->save($tempPath);

            if (!file_exists($tempPath)) {
                return null;
            }

            // Move to resolutions directory
            $finalPath = 'resolutions/' . $filename;
            Storage::put($finalPath, file_get_contents($tempPath));

            // Clean up temp file
            @unlink($tempPath);

            return $filename;
        } catch (\Exception $e) {
            Log::error('Failed to generate resolution: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Attach document to purchase request
     */
    private function attachToDocuments(string $filename): Document
    {
        // Check if resolution already exists, update it instead
        $existingDoc = Document::where('documentable_type', PurchaseRequest::class)
            ->where('documentable_id', $this->purchaseRequest->id)
            ->where('document_type', 'bac_resolution')
            ->first();

        if ($existingDoc) {
            // Update existing document
            $existingDoc->update([
                'document_number' => 'RES-' . now()->format('Y-m-d-His'),
                'title' => 'BAC Resolution - ' . $this->data['resolution_no'],
                'file_name' => $filename,
                'file_path' => 'resolutions/' . $filename,
                'file_extension' => 'docx',
                'file_size' => Storage::size('resolutions/' . $filename),
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'version' => $existingDoc->version + 1,
                'status' => 'approved',
            ]);

            return $existingDoc;
        }

        // Create new document record
        return Document::create([
            'document_number' => 'RES-' . now()->format('Y-m-d-His'),
            'documentable_type' => PurchaseRequest::class,
            'documentable_id' => $this->purchaseRequest->id,
            'document_type' => 'bac_resolution',
            'title' => 'BAC Resolution - ' . $this->data['resolution_no'],
            'description' => 'BAC Resolution for Purchase Request ' . $this->data['purchase_request_no'],
            'file_name' => $filename,
            'file_path' => 'resolutions/' . $filename,
            'file_extension' => 'docx',
            'file_size' => Storage::size('resolutions/' . $filename),
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'version' => 1,
            'is_current_version' => true,
            'uploaded_by' => Auth::id() ?? 1,
            'is_public' => false,
            'visible_to_roles' => json_encode(['BAC Secretariat', 'CEO', 'Budget Office']),
            'status' => 'approved',
        ]);
    }
}

