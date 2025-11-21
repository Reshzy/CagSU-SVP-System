<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class QuotationController extends Controller
{
    public function downloadSample(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $supplierDefaults = [
            ['name' => 'AW COMMERCIAL', 'location' => 'SANCHEZ MIRA, CAGAYAN'],
            ['name' => 'MIGRANTS SCHOOL AND OFFICE SUPPLIES', 'location' => 'SANCHEZ MIRA, CAGAYAN'],
            ['name' => "LIENMAVEL SHOPPER'S MART", 'location' => 'SANCHEZ MIRA, CAGAYAN'],
            ['name' => '', 'location' => ''],
        ];

        $signatoryDefaults = [
            ['position' => 'BAC Chairman', 'name' => 'Christopher R. Garingan'],
            ['position' => 'BAC Vice Chairman', 'name' => 'Atty. Jan Leandro P. Verzon'],
            ['position' => 'BAC Member', 'name' => 'Melvin S. Atayan'],
            ['position' => 'BAC Member', 'name' => 'Valentin M. Apostol'],
            ['position' => 'BAC Member', 'name' => 'Chris Ian T. Rodriguez'],
        ];

        $approverDefaults = [
            'head_bac' => 'Christie Melflor L. Aguirre',
            'ceo' => 'Rodel Francisco T. Alegado, Ph.D.',
        ];

        $supplierInput = $request->input('suppliers', []);
        $supplierInput = is_array($supplierInput) ? array_values($supplierInput) : [];
        $supplierCount = max(4, count($supplierInput), count($supplierDefaults));
        $suppliers = [];
        for ($i = 0; $i < $supplierCount; $i++) {
            $defaults = $supplierDefaults[$i] ?? ['name' => 'SUPPLIER ' . ($i + 1), 'location' => ''];
            $name = trim($supplierInput[$i]['name'] ?? $defaults['name']);
            $location = trim($supplierInput[$i]['location'] ?? $defaults['location']);

            $suppliers[] = [
                'name' => $name !== '' ? $name : $defaults['name'],
                'location' => $location !== '' ? $location : $defaults['location'],
            ];
        }

        $supplierCount = count($suppliers);

        $signatoriesInput = $request->input('signatories', []);
        $signatories = [];
        foreach ($signatoryDefaults as $index => $defaults) {
            $input = $signatoriesInput[$index] ?? [];
            $name = trim((string) ($input['name'] ?? $defaults['name']));
            $signatories[] = [
                'position' => $defaults['position'],
                'name' => $name !== '' ? $name : $defaults['name'],
            ];
        }

        $approverInput = $request->input('approvers', []);
        $headBacName = trim((string) ($approverInput['head_bac'] ?? $approverDefaults['head_bac']));
        if ($headBacName === '') {
            $headBacName = $approverDefaults['head_bac'];
        }

        $ceoName = trim((string) ($approverInput['ceo'] ?? $approverDefaults['ceo']));
        if ($ceoName === '') {
            $ceoName = $approverDefaults['ceo'];
        }

        $itemWinnerInput = $request->input('item_winners', []);
        $itemWinnerOverrides = [];

        if (is_array($itemWinnerInput)) {
            foreach ($itemWinnerInput as $itemIndex => $winnerValues) {
                if (!is_array($winnerValues)) {
                    continue;
                }

                $normalized = [];
                foreach ($winnerValues as $value) {
                    if ($value === '' || $value === null) {
                        continue;
                    }

                    if (is_numeric($value)) {
                        $supplierIndex = (int) $value;
                        if ($supplierIndex >= 0 && $supplierIndex < $supplierCount) {
                            $normalized[] = $supplierIndex;
                        }
                    }
                }

                $normalized = array_values(array_unique($normalized));
                if (count($normalized) > 0) {
                    $itemWinnerOverrides[(int) $itemIndex] = $normalized;
                }
            }
        }

        $itemDefaults = [
            [
                'qty' => 1,
                'unit' => 'BOX',
                'article' => 'EXAMINATION GLOVES (SURGICAL GLOVES), 100PC/BOX, LARGE',
                'prices' => [
                    ['u' => 'NONE', 't' => null],
                    ['u' => 650, 't' => 650],
                    ['u' => 'NONE', 't' => null],
                    ['u' => '', 't' => ''],
                ],
            ],
            [
                'qty' => 1,
                'unit' => 'PACK',
                'article' => 'CLEAR POLYETHYLENE (6x12), 100PCS/PACK, 0.002 THICKNESS',
                'prices' => [
                    ['u' => 90.5, 't' => 90.5],
                    ['u' => 'NONE', 't' => null],
                    ['u' => 0, 't' => 0],
                    ['u' => '', 't' => ''],
                ],
            ],
        ];

        $itemInput = $request->input('items', []);
        $itemInput = is_array($itemInput) ? array_values($itemInput) : [];
        if (count($itemInput) === 0) {
            $itemInput = $itemDefaults;
        }

        $items = [];
        foreach ($itemInput as $index => $rawItem) {
            $defaults = $itemDefaults[$index] ?? ['qty' => 1, 'unit' => '', 'article' => '', 'prices' => []];
            $qty = (int) ($rawItem['qty'] ?? $defaults['qty'] ?? 1);
            $qty = $qty > 0 ? $qty : 1;
            $unit = strtoupper(trim((string) ($rawItem['unit'] ?? $defaults['unit'] ?? '')));
            $article = trim((string) ($rawItem['article'] ?? $defaults['article'] ?? ''));

            $prices = [];
            for ($s = 0; $s < $supplierCount; $s++) {
                $priceDefaults = $defaults['prices'][$s] ?? ['u' => '', 't' => ''];
                $rawPrices = $rawItem['prices'][$s] ?? [];
                $unitPrice = $rawPrices['u'] ?? $priceDefaults['u'];
                $totalPrice = $rawPrices['t'] ?? $priceDefaults['t'];

                // Auto-calculate total price if unit price is numeric and total is missing/empty
                if (is_numeric($unitPrice) && $unitPrice > 0) {
                    $unitPriceFloat = (float) $unitPrice;
                    // If total is empty, null, or not numeric, calculate it
                    if (empty($totalPrice) || !is_numeric($totalPrice)) {
                        $totalPrice = $qty * $unitPriceFloat;
                    } else {
                        // Keep manually entered total if it exists
                        $totalPrice = (float) $totalPrice;
                    }
                } else {
                    // Handle non-numeric values like "NONE" or empty strings
                    $totalPrice = $totalPrice ?? '';
                }

                $prices[$s] = [
                    'u' => $unitPrice,
                    't' => $totalPrice,
                ];
            }

            $items[] = [
                'no' => $index + 1,
                'qty' => $qty,
                'unit' => $unit !== '' ? $unit : $defaults['unit'],
                'article' => $article !== '' ? $article : $defaults['article'],
                'prices' => $prices,
            ];
        }

        if (count($items) === 0) {
            foreach ($itemDefaults as $index => $defaultItem) {
                $defaultItem['no'] = $index + 1;
                $items[] = $defaultItem;
            }
        }

        // Calculate Total Price (sum of all bids) and track winners for Total Amount Awarded
        $totalPrices = array_fill(0, $supplierCount, 0.0);
        $hasTotalPrices = array_fill(0, $supplierCount, false);
        $itemWinners = []; // Track which supplier(s) won each item

        foreach ($items as $itemIndex => &$item) {
            // Find the lowest total price for this item to determine winners
            $lowestPrice = null;
            $lowestPriceSuppliers = [];
            
            foreach ($suppliers as $supplierIndex => $supplier) {
                $price = $item['prices'][$supplierIndex] ?? ['u' => '', 't' => ''];
                $totalPrice = $price['t'] ?? '';
                
                if (is_numeric($totalPrice) && $totalPrice > 0) {
                    $totalPriceFloat = (float) $totalPrice;
                    // Calculate Total Price (all bids)
                    $totalPrices[$supplierIndex] += $totalPriceFloat;
                    $hasTotalPrices[$supplierIndex] = true;
                    
                    // Track winners for Total Amount Awarded
                    if ($lowestPrice === null || $totalPriceFloat < $lowestPrice) {
                        $lowestPrice = $totalPriceFloat;
                        $lowestPriceSuppliers = [$supplierIndex];
                    } elseif ($totalPriceFloat === $lowestPrice) {
                        $lowestPriceSuppliers[] = $supplierIndex;
                    }
                }
            }
            
            // Apply manual overrides only when there is at least one valid tie
            $override = $itemWinnerOverrides[$itemIndex] ?? null;
            if (
                is_array($override)
                && count($override) > 0
                && count($lowestPriceSuppliers) > 1
            ) {
                $manualSubset = array_values(array_intersect($lowestPriceSuppliers, $override));
                if (count($manualSubset) > 0) {
                    $lowestPriceSuppliers = $manualSubset;
                }
            }

            // Store winners for this item
            $itemWinners[$itemIndex] = $lowestPriceSuppliers;
            
            // Clean up price values
            foreach ($suppliers as $supplierIndex => $supplier) {
                $unitValue = $item['prices'][$supplierIndex]['u'] ?? '';
                $totalValue = $item['prices'][$supplierIndex]['t'] ?? '';
                $item['prices'][$supplierIndex]['u'] = $unitValue ?? '';
                $item['prices'][$supplierIndex]['t'] = $totalValue ?? '';
            }
        }
        unset($item);

        // Calculate Total Amount Awarded (only winning bids)
        $totalAmountAwarded = array_fill(0, $supplierCount, 0.0);
        $hasAwarded = array_fill(0, $supplierCount, false);
        
        foreach ($items as $itemIndex => $item) {
            $winners = $itemWinners[$itemIndex] ?? [];
            foreach ($winners as $supplierIndex) {
                $totalValue = $item['prices'][$supplierIndex]['t'] ?? '';
                if (is_numeric($totalValue) && $totalValue > 0) {
                    $totalAmountAwarded[$supplierIndex] += (float) $totalValue;
                    $hasAwarded[$supplierIndex] = true;
                }
            }
        }

        // Convert to null if no values
        $totalPrices = array_map(
            static fn ($sum, $hasValue) => $hasValue ? $sum : null,
            $totalPrices,
            $hasTotalPrices
        );
        
        $totalAmountAwarded = array_map(
            static fn ($sum, $hasValue) => $hasValue ? $sum : null,
            $totalAmountAwarded,
            $hasAwarded
        );

        $documentMetaDefaults = [
            'purpose' => 'FOR THE IMPLEMENTATION AND CONDUT OF TRAINING UNDER EXTENSION PROGRAM SARANAY TITLED, “HOLISTIC COMMUNITY EMPOWERMENT THROUGH CAPACITY-BUILDING ON HEALTH, SANITATION, SAFETY, LIFELIHOOD, AND FINANCIAL LITERACY”',
            'aoq_no' => '2025.0249',
            'pr_no' => '11.2025.0652',
            'rfq_no' => '2025.0785-0787',
            'date' => '11.14.2025',
        ];

        $documentMeta = [
            'purpose' => $request->input('purpose', $documentMetaDefaults['purpose']),
            'aoq_no' => $request->input('aoq_no', $documentMetaDefaults['aoq_no']),
            'pr_no' => $request->input('pr_no', $documentMetaDefaults['pr_no']),
            'rfq_no' => $request->input('rfq_no', $documentMetaDefaults['rfq_no']),
            'date' => $request->input('date', $documentMetaDefaults['date']),
        ];

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Century Gothic');
        $phpWord->setDefaultFontSize(7);
        $section = $phpWord->addSection([
            'marginLeft' => Converter::inchToTwip(0.25),
            'marginRight' => Converter::inchToTwip(0.25),
            'marginTop' => Converter::inchToTwip(0.25),
            'marginBottom' => Converter::inchToTwip(0.25),
            'orientation' => 'landscape',
            'pageSizeW' => Converter::inchToTwip(13),
            'pageSizeH' => Converter::inchToTwip(8.5),
        ]);

        $phpWord->addTableStyle(
            'quotationTable',
            [
                'borderSize' => 1,
                'borderColor' => '000000',
                'cellMargin' => 5,
            ],
            [
                'alignment' => JcTable::CENTER,
            ]
        );

        $phpWord->addTableStyle(
            'metaTable',
            [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
            ],
            [
                'alignment' => JcTable::CENTER,
            ]
        );

        $phpWord->addTableStyle(
            'certificationWrapperTable',
            [
                'borderSize' => 0,
                'borderColor' => 'ffffff',
                'cellMargin' => 10,
            ],
            [
                'alignment' => JcTable::CENTER,
            ]
        );

        $phpWord->addTableStyle(
            'certificationSummaryTable',
            [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
            ],
            [
                'alignment' => JcTable::START,
            ]
        );

        $phpWord->addTableStyle(
            'certificationTable',
            [
                'borderSize' => 1,
                'borderColor' => '000000',
                'borderTopSize' => 1,
                'borderTopColor' => '000000',
                'borderBottomSize' => 1,
                'borderBottomColor' => '000000',
                'borderLeftSize' => 1,
                'borderLeftColor' => '000000',
                'borderRightSize' => 1,
                'borderRightColor' => '000000',
                'borderInsideV' => 0,
                'borderInsideH' => 1,
                'cellMargin' => 10,
            ],
            [
                'alignment' => JcTable::CENTER,
            ]
        );

        $phpWord->addTableStyle(
            'signatureTable',
            [
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
            ],
            [
                'alignment' => JcTable::CENTER,
            ]
        );

        $header = ['bold' => false, 'size' => 5, 'name' => 'Century Gothic'];
        $supplierHeader = ['bold' => true, 'size' => 8, 'name' => 'Century Gothic'];
        $locationStyle = ['bold' => false, 'size' => 5, 'name' => 'Century Gothic'];
        $priceLabelStyle = ['bold' => false, 'size' => 5, 'name' => 'Century Gothic'];
        $certificationTextStyle = ['bold' => false, 'size' => 5, 'name' => 'Century Gothic'];
        $certificationLabelStyle = ['bold' => false, 'size' => 5, 'name' => 'Century Gothic'];
        $certificationValueStyle = ['bold' => true, 'size' => 5, 'name' => 'Century Gothic'];
        $signaturePositionStyle = ['bold' => true, 'size' => 6, 'name' => 'Century Gothic'];
        $signatureNameStyle = ['bold' => true, 'size' => 7, 'name' => 'Century Gothic', 'allCaps' => true];

        $noSpacing = ['spaceAfter' => 0, 'spaceBefore' => 0];
        $paragraphCenter = array_merge($noSpacing, ['alignment' => Jc::CENTER]);
        $paragraphLeft = array_merge($noSpacing, ['alignment' => Jc::START]);
        $paragraphRight = array_merge($noSpacing, ['alignment' => Jc::END]);
        $cellMiddle = ['valign' => 'center'];
        $dataText = ['size' => 7, 'name' => 'Century Gothic'];

        $metaLabelStyle = ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'];
        $metaValueStyle = ['bold' => true, 'size' => 6, 'name' => 'Century Gothic', 'underline' => 'single'];
        $metaPurposeStyle = ['bold' => true, 'size' => 6, 'name' => 'Century Gothic', 'underline' => 'single'];
        $metaCellStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF'];
        $metaRowHeight = Converter::inchToTwip(0.15);
        $metaColumnWidths = [
            'purposeLabel' => Converter::inchToTwip(0.5),
            'purposeValue' => Converter::inchToTwip(6.6),
            'leftLabel' => Converter::inchToTwip(0.85),
            'leftValue' => Converter::inchToTwip(1.5),
            'rightLabel' => Converter::inchToTwip(0.85),
            'rightValue' => Converter::inchToTwip(1.5),
        ];

        $tightHeaderParagraph = array_merge(
            $paragraphCenter,
            [
                'spacing' => 0,
                'spaceBefore' => 0,
                'spaceAfter' => 0,
                'lineSpacingRule' => 'exact',
                'lineSpacing' => 120,
            ]
        );

        $section->addText('Republic of the Philippines', ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'], $tightHeaderParagraph);
        $section->addText('CAGAYAN STATE UNIVERSITY', ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'], $tightHeaderParagraph);
        $section->addText('Sanchez Mira, Cagayan', ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'], $tightHeaderParagraph);
        $section->addText('ABSTRACT OF QUOTATIONS', ['bold' => true, 'size' => 6, 'name' => 'Century Gothic'], $tightHeaderParagraph);

        $metaTable = $section->addTable('metaTable');
        $metaTable->addRow($metaRowHeight, ['exactHeight' => true]);
        $metaTable->addCell($metaColumnWidths['purposeLabel'], ['valign' => 'center', 'vMerge' => 'restart'] + $metaCellStyle)
            ->addText('PURPOSE:', $metaLabelStyle, $paragraphLeft);
        $metaTable->addCell($metaColumnWidths['purposeValue'], ['valign' => 'center', 'vMerge' => 'restart'] + $metaCellStyle)
            ->addText($documentMeta['purpose'], $metaPurposeStyle, $paragraphLeft);
        $metaTable->addCell($metaColumnWidths['leftLabel'], ['valign' => 'center'] + $metaCellStyle)
            ->addText('AOQ. NO.: ‎ ', $metaLabelStyle, $paragraphRight);
        $metaTable->addCell($metaColumnWidths['leftValue'], ['valign' => 'center'] + $metaCellStyle)
            ->addText($documentMeta['aoq_no'], $metaValueStyle, $paragraphLeft);
        $metaTable->addCell($metaColumnWidths['rightLabel'], ['valign' => 'center'] + $metaCellStyle)
            ->addText('RFQ.NO.: ‎ ', $metaLabelStyle, $paragraphRight);
        $metaTable->addCell($metaColumnWidths['rightValue'], ['valign' => 'center'] + $metaCellStyle)
            ->addText($documentMeta['rfq_no'], $metaValueStyle, $paragraphLeft);

        $metaTable->addRow($metaRowHeight, ['exactHeight' => true]);
        $metaTable->addCell(
            $metaColumnWidths['purposeLabel'],
            ['valign' => 'top', 'vMerge' => 'continue'] + $metaCellStyle
        );
        $metaTable->addCell(
            $metaColumnWidths['purposeValue'],
            ['valign' => 'top', 'vMerge' => 'continue'] + $metaCellStyle
        );
        $metaTable->addCell($metaColumnWidths['leftLabel'], ['valign' => 'top'] + $metaCellStyle)
            ->addText('P.R. NO.: ‎ ', $metaLabelStyle, $paragraphRight);
        $metaTable->addCell($metaColumnWidths['leftValue'], ['valign' => 'top'] + $metaCellStyle)
            ->addText($documentMeta['pr_no'], $metaValueStyle, $paragraphLeft);
        $metaTable->addCell($metaColumnWidths['rightLabel'], ['valign' => 'top'] + $metaCellStyle)
            ->addText('DATE: ‎ ', $metaLabelStyle, $paragraphRight);
        $metaTable->addCell($metaColumnWidths['rightValue'], ['valign' => 'top'] + $metaCellStyle)
            ->addText($documentMeta['date'], $metaValueStyle, $paragraphLeft);

        $section->addTextBreak(0.05);

        $table = $section->addTable('quotationTable');

        $widths = [
            'no' => 250,
            'qty' => 350,
            'unit' => 500,
            'article' => 5200,
            'vendor' => 1500,
        ];

        // Header row 1
        $table->addRow();
        foreach (['NO.', 'QTY.', 'UNIT', 'ARTICLES TO BE PURCHASED WORK TO BE DONE'] as $index => $label) {
            $table->addCell(
                array_values($widths)[$index],
                ['vMerge' => 'restart'] + $cellMiddle
            )->addText($label, $header, $paragraphCenter);
        }

        foreach ($suppliers as $supplier) {
            $table->addCell($widths['vendor'] * 2, ['gridSpan' => 2] + $cellMiddle)
                ->addText($supplier['name'], $supplierHeader, $paragraphCenter);
        }

        // Header row 2 (locations)
        $table->addRow();
        for ($i = 0; $i < 4; $i++) {
            $table->addCell(array_values($widths)[$i], ['vMerge' => 'continue']);
        }

        foreach ($suppliers as $supplier) {
            $table->addCell($widths['vendor'] * 2, ['gridSpan' => 2] + $cellMiddle)
                ->addText($supplier['location'], $locationStyle, $paragraphCenter);
        }

        // Header row 3 (price labels)
        $table->addRow();
        for ($i = 0; $i < 4; $i++) {
            $table->addCell(array_values($widths)[$i], ['vMerge' => 'continue']);
        }

        foreach ($suppliers as $_) {
            $table->addCell($widths['vendor'], $cellMiddle)
                ->addText('U.PRICE', $priceLabelStyle, $paragraphCenter);
            $table->addCell($widths['vendor'], $cellMiddle)
                ->addText('T.PRICE', $priceLabelStyle, $paragraphCenter);
        }

        // Data rows
        foreach ($items as $itemIndex => $item) {
            $winners = $itemWinners[$itemIndex] ?? [];
            
            $table->addRow();
            $table->addCell($widths['no'], $cellMiddle)->addText((string) $item['no'], $dataText, $paragraphCenter);
            $table->addCell($widths['qty'], $cellMiddle)->addText((string) $item['qty'], $dataText, $paragraphCenter);
            $table->addCell($widths['unit'], $cellMiddle)->addText($item['unit'], $dataText, $paragraphCenter);
            $table->addCell($widths['article'], ['valign' => 'top'])
                ->addText($item['article'], $dataText, $paragraphLeft);

            foreach ($suppliers as $supplierIndex => $supplier) {
                $price = $item['prices'][$supplierIndex] ?? ['u' => '', 't' => ''];
                $isLowest = in_array($supplierIndex, $winners);
                
                // Add yellow background if this supplier has the lowest price
                $cellStyle = $cellMiddle;
                if ($isLowest) {
                    $cellStyle = array_merge($cellMiddle, ['bgColor' => 'FFFF00']);
                }
                
                // Format unit price
                $unitPrice = $price['u'] ?? '';
                $unitPriceFormatted = $unitPrice;
                if (is_numeric($unitPrice) && $unitPrice !== '') {
                    $unitPriceFormatted = number_format((float) $unitPrice, 2);
                }
                
                // Format total price
                $totalPrice = $price['t'] ?? '';
                $totalPriceFormatted = $totalPrice;
                if (is_numeric($totalPrice) && $totalPrice !== '') {
                    $totalPriceFormatted = number_format((float) $totalPrice, 2);
                }
                
                // Determine alignment: left for "NONE", right for numeric values
                $unitPriceAlign = (strtoupper(trim((string) $unitPriceFormatted)) === 'NONE') ? $paragraphLeft : $paragraphRight;
                $totalPriceAlign = (strtoupper(trim((string) $totalPriceFormatted)) === 'NONE') ? $paragraphLeft : $paragraphRight;
                
                $table->addCell($widths['vendor'], $cellStyle)
                    ->addText($unitPriceFormatted, $dataText, $unitPriceAlign);
                $table->addCell($widths['vendor'], $cellStyle)
                    ->addText($totalPriceFormatted, $dataText, $totalPriceAlign);
            }
        }

        // Ensure at least 10 visible item rows by adding blank placeholders
        $minimumVisibleRows = 10;
        $currentRowCount = count($items);
        for ($rowNumber = $currentRowCount + 1; $rowNumber <= $minimumVisibleRows; $rowNumber++) {
            $table->addRow();
            $table->addCell($widths['no'], $cellMiddle)->addText(' ', $dataText, $paragraphCenter);
            $table->addCell($widths['qty'], $cellMiddle)->addText(' ', $dataText, $paragraphCenter);
            $table->addCell($widths['unit'], $cellMiddle)->addText(' ', $dataText, $paragraphCenter);
            $table->addCell($widths['article'], ['valign' => 'top'])->addText(' ', $dataText, $paragraphLeft);

            for ($supplierIndex = 0; $supplierIndex < $supplierCount; $supplierIndex++) {
                $table->addCell($widths['vendor'], $cellMiddle)->addText(' ', $dataText, $paragraphRight);
                $table->addCell($widths['vendor'], $cellMiddle)->addText(' ', $dataText, $paragraphRight);
            }
        }

        // Total Price row (sum of all bids)
        $table->addRow();
        $baseColumns = [
            $widths['no'],
            $widths['qty'],
            $widths['unit'],
            $widths['article'],
        ];
        $table->addCell(array_sum($baseColumns), ['gridSpan' => 4] + $cellMiddle)
            ->addText('TOTAL PRICE', ['bold' => true, 'name' => 'Century Gothic'], $paragraphRight);
        $totalPriceStyle = ['name' => 'Century Gothic', 'underline' => 'single'];
        foreach ($suppliers as $supplierIndex => $supplier) {
            $table->addCell($widths['vendor'], $cellMiddle)
                ->addText('', null, $paragraphRight);
            $table->addCell($widths['vendor'], $cellMiddle)
                ->addText($totalPrices[$supplierIndex] !== null ? number_format($totalPrices[$supplierIndex], 2) : '', $totalPriceStyle, $paragraphRight);
        }

        // Total Amount Awarded row (sum of winning bids only)
        $table->addRow();
        $table->addCell(array_sum($baseColumns), ['gridSpan' => 4] + $cellMiddle)
            ->addText('TOTAL AMOUNT AWARDED', ['bold' => true, 'color' => 'FF0000', 'name' => 'Century Gothic'], $paragraphRight);
        $totalAmountAwardedStyle = ['name' => 'Century Gothic', 'underline' => 'single', 'bold' => true];
        foreach ($suppliers as $supplierIndex => $supplier) {
            $table->addCell($widths['vendor'], $cellMiddle)
                ->addText('', null, $paragraphRight);
            
            // Add yellow background if value is not empty
            $awardedCellStyle = $cellMiddle;
            if ($totalAmountAwarded[$supplierIndex] !== null) {
                $awardedCellStyle = array_merge($cellMiddle, ['bgColor' => 'FFFF00']);
            }
            
            $table->addCell($widths['vendor'], $awardedCellStyle)
                ->addText($totalAmountAwarded[$supplierIndex] !== null ? number_format($totalAmountAwarded[$supplierIndex], 2) : '', $totalAmountAwardedStyle, $paragraphRight);
        }

        $section->addText(' ', ['size' => 2], ['spaceAfter' => 0, 'spaceBefore' => 0]);

        $certificationParagraphs = [
            'WE HEREBY CERTIFY that we, the members of the Bids and Awards Committee have opened, evaluated and ranked the above mentioned bid proposals under the alternative mode of procurement, negotiated procurement (small value) under Sec. 53.9 of the Revised IRR of R.A. 9184.',
            'After careful deliberation, the committee has decided to recommend the procurement items to the lowest bidder whose price offered is considered reasonable and advantageous to the best interest.',
        ];

        $certificationWrapper = $section->addTable('certificationWrapperTable');
        $certificationWrapper->addRow();
        $certificationTextCell = $certificationWrapper->addCell(Converter::inchToTwip(7.5), ['valign' => 'top']);
        $certificationTableCell = $certificationWrapper->addCell(Converter::inchToTwip(5.1), ['valign' => 'top']);

        $certificationParagraphStyle = array_merge(
            $paragraphLeft,
            ['indentation' => ['hanging' => Converter::inchToTwip(-0.25)]]
        );
        foreach ($certificationParagraphs as $text) {
            $certificationTextCell->addText($text, $certificationTextStyle, $certificationParagraphStyle);
        }

        $certificationTextCell->addTextBreak();

        $certificationColumnWidths = [
            'itemLabel' => Converter::inchToTwip(1.0),
            'itemValue' => Converter::inchToTwip(0.7),
            'awardLabel' => Converter::inchToTwip(1.2),
            'awardValue' => Converter::inchToTwip(2.5),
        ];

        $summaryRowHeight = Converter::inchToTwip(0.01);
        $summaryTable = $certificationTableCell->addTable('certificationSummaryTable');
        $summaryTable->addRow($summaryRowHeight, ['exactHeight' => true]);
        $summaryTable->addCell(array_sum($certificationColumnWidths), ['valign' => 'center'])
            ->addText(
                'SUMMARY OF AWARDS',
                ['bold' => true, 'size' => 1, 'name' => 'Century Gothic'],
                $paragraphLeft
            );

        $certificationCellBorders = [
            'itemLabel' => [
                'borderLeftSize' => 1,
                'borderLeftColor' => '000000',
                'borderRightSize' => 0,
                'borderRightColor' => 'ffffff',
                'borderTopSize' => 1,
                'borderTopColor' => '000000',
                'borderBottomSize' => 1,
                'borderBottomColor' => '000000',
            ],
            'itemValue' => [
                'borderLeftSize' => 0,
                'borderLeftColor' => 'ffffff',
                'borderRightSize' => 0,
                'borderRightColor' => 'ffffff',
                'borderTopSize' => 1,
                'borderTopColor' => '000000',
                'borderBottomSize' => 1,
                'borderBottomColor' => '000000',
            ],
            'awardLabel' => [
                'borderLeftSize' => 0,
                'borderLeftColor' => 'ffffff',
                'borderRightSize' => 0,
                'borderRightColor' => 'ffffff',
                'borderTopSize' => 1,
                'borderTopColor' => '000000',
                'borderBottomSize' => 1,
                'borderBottomColor' => '000000',
            ],
            'awardValue' => [
                'borderLeftSize' => 1,  // Changed from 0 to 6
                'borderLeftColor' => '000000',  // Added this line
                'borderRightSize' => 1,
                'borderRightColor' => '000000',
                'borderTopSize' => 1,
                'borderTopColor' => '000000',
                'borderBottomSize' => 1,
                'borderBottomColor' => '000000',
            ],
        ];


        $certificationTable = $certificationTableCell->addTable('certificationTable');

        $certificationEntries = [];

        foreach ($items as $itemIndex => $item) {
            $winners = $itemWinners[$itemIndex] ?? [];
            $awardee = '';

            if (count($winners) > 0) {
                $primaryWinner = $winners[0];
                $awardee = $suppliers[$primaryWinner]['name'] ?? '';
            } else {
                $lowestPrice = null;
                foreach ($suppliers as $supplierIndex => $supplier) {
                    $price = $item['prices'][$supplierIndex]['t'] ?? null;
                    if (is_numeric($price) && $price > 0 && ($lowestPrice === null || $price < $lowestPrice)) {
                        $lowestPrice = (float) $price;
                        $awardee = $supplier['name'];
                    }
                }
            }

            $certificationEntries[] = [
                'item_no' => $item['no'],
                'awardee' => $awardee,
            ];
        }

        while (count($certificationEntries) < 3) {
            $certificationEntries[] = ['item_no' => '', 'awardee' => ''];
        }

        $certificationRowHeight = Converter::inchToTwip(0.10);

        foreach ($certificationEntries as $index => $entry) {
            $itemValue = (string) $entry['item_no'];
            $awardValue = $entry['awardee'];

            $certificationTable->addRow($certificationRowHeight, ['exactHeight' => true]);
            $certificationTable->addCell($certificationColumnWidths['itemLabel'], array_merge($cellMiddle, $certificationCellBorders['itemLabel']))
                ->addText('ITEM NO.', $certificationLabelStyle, $paragraphLeft);
            $certificationTable->addCell($certificationColumnWidths['itemValue'], array_merge($cellMiddle, $certificationCellBorders['itemValue']))
                ->addText($itemValue !== '' ? $itemValue : ' ', $certificationValueStyle, $paragraphCenter);
            $certificationTable->addCell($certificationColumnWidths['awardLabel'], array_merge($cellMiddle, $certificationCellBorders['awardLabel']))
                ->addText('AWARDED TO', $certificationLabelStyle, $paragraphLeft);
            $certificationTable->addCell($certificationColumnWidths['awardValue'], array_merge($cellMiddle, $certificationCellBorders['awardValue']))
                ->addText($awardValue !== '' ? $awardValue : ' ', $certificationValueStyle, $paragraphLeft);
        }

        // Signature table
        $signatureTable = $section->addTable('signatureTable');
        $signatureColumnWidth = Converter::inchToTwip(2.5);
        
        $signatureTable->addRow();
        foreach ($signatories as $signatory) {
            $cell = $signatureTable->addCell($signatureColumnWidth, ['valign' => 'top']);
            $cell->addText(' ', $signatureNameStyle, $paragraphCenter); // Space above line
            $cell->addTextBreak(0.3);
            // $cell->addText('_________________________', $signatureNameStyle, $paragraphCenter);
            $cell->addTextBreak(0.1);
            $cell->addText($signatory['name'] ?? '', $signatureNameStyle, $paragraphCenter);
            $cell->addText($signatory['position'] ?? '', $signaturePositionStyle, $paragraphCenter);
        }

        $section->addText(' ', ['size' => 10], ['spaceAfter' => 0, 'spaceBefore' => 0]);
        $section->addText(
            'I hereby certify that the foregoing is a true and correct copy of the Abstract of Quotation regularly presented to and adopted by the bids and awards committee and that the signatures set',
            ['size' => 5, 'name' => 'Century Gothic'],
            array_merge($paragraphLeft, ['indentation' => ['left' => Converter::inchToTwip(0.25)]])
        );
        $section->addText(
            'above the respected names of the committee members are their true and genuine signatures.',
            ['size' => 5, 'name' => 'Century Gothic'],
            array_merge($paragraphLeft)
        );

        $approverTable = $section->addTable('signatureTable');
        $approverColumnWidth = Converter::inchToTwip(4.0);
        $approverTable->addRow();

        $bacHeadCell = $approverTable->addCell($approverColumnWidth, ['valign' => 'bottom']);
        $bacHeadCell->addText($headBacName, $signatureNameStyle, $paragraphCenter);
        $bacHeadCell->addTextBreak(0.3);
        $bacHeadCell->addText('HEAD - BAC Secretariat', $signaturePositionStyle, $paragraphCenter);

        $approvedCell = $approverTable->addCell($approverColumnWidth, ['valign' => 'bottom']);
        $approvedCell->addText('ㅤㅤㅤㅤㅤAPPROVED BY:', ['bold' => false, 'size' => 6, 'name' => 'Century Gothic'], $paragraphLeft);
        $approvedCell->addText($ceoName, $signatureNameStyle, $paragraphCenter);
        $approvedCell->addTextBreak(0.3);
        $approvedCell->addText('Campus Executive Officer', $signaturePositionStyle, $paragraphCenter);

        $approverTable->addCell($approverColumnWidth, ['valign' => 'bottom'])
            ->addText(' ', $signatureNameStyle, $paragraphCenter);

        $fileName = 'quotation-sample.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName);
    }
}
