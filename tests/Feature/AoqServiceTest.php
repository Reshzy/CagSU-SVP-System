<?php

namespace Tests\Feature;

use App\Models\AoqItemDecision;
use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AoqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AoqServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AoqService $aoqService;

    protected User $bacUser;

    protected PurchaseRequest $pr;

    protected PurchaseRequestItem $prItem;

    protected array $suppliers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aoqService = new AoqService;

        // Create BAC user
        $this->bacUser = User::factory()->create();

        // Create department
        $department = Department::create([
            'department_code' => 'TEST',
            'department_name' => 'Test Department',
            'allocated_budget' => 100000,
        ]);

        // Create purchase request
        $this->pr = PurchaseRequest::create([
            'pr_number' => 'PR-0126-0001',
            'requester_id' => $this->bacUser->id,
            'department_id' => $department->id,
            'purpose' => 'Test procurement',
            'status' => 'bac_evaluation',
        ]);

        // Create PR item
        $this->prItem = PurchaseRequestItem::create([
            'purchase_request_id' => $this->pr->id,
            'item_name' => 'Test Item',
            'unit_of_measure' => 'PC',
            'quantity_requested' => 10,
            'estimated_unit_cost' => 100.00,
            'estimated_total_cost' => 1000.00,
        ]);

        // Create suppliers
        $this->suppliers = [
            Supplier::create([
                'supplier_code' => 'SUP-001',
                'business_name' => 'Supplier A',
                'status' => 'active',
            ]),
            Supplier::create([
                'supplier_code' => 'SUP-002',
                'business_name' => 'Supplier B',
                'status' => 'active',
            ]),
            Supplier::create([
                'supplier_code' => 'SUP-003',
                'business_name' => 'Supplier C',
                'status' => 'active',
            ]),
        ];
    }

    /** @test */
    public function it_calculates_winner_with_no_ties()
    {
        // Create quotations with different prices
        $this->createQuotation($this->suppliers[0], 90.00);  // Lowest
        $this->createQuotation($this->suppliers[1], 95.00);
        $this->createQuotation($this->suppliers[2], 100.00);

        // Calculate winners
        $result = $this->aoqService->calculateWinnersAndTies($this->pr);

        // Assert
        $this->assertArrayHasKey($this->prItem->id, $result);
        $itemResult = $result[$this->prItem->id];

        $this->assertFalse($itemResult['has_tie'], 'Should not have a tie');
        $this->assertCount(1, $itemResult['winners'], 'Should have exactly one winner');
        $this->assertEquals(900.00, $itemResult['lowest_price'], 'Lowest price should be 900.00');

        // Check that ranks are assigned correctly
        $quotes = QuotationItem::where('purchase_request_item_id', $this->prItem->id)
            ->orderBy('rank')
            ->get();

        $this->assertEquals(1, $quotes[0]->rank);
        $this->assertEquals(2, $quotes[1]->rank);
        $this->assertEquals(3, $quotes[2]->rank);

        // Check that winner is marked
        $this->assertTrue($quotes[0]->is_winner);
        $this->assertTrue($quotes[0]->is_lowest);
        $this->assertFalse($quotes[0]->is_tied);
    }

    /** @test */
    public function it_detects_ties_correctly()
    {
        // Create quotations with tied prices
        $this->createQuotation($this->suppliers[0], 90.00);  // Tied for lowest
        $this->createQuotation($this->suppliers[1], 90.00);  // Tied for lowest
        $this->createQuotation($this->suppliers[2], 100.00);

        // Calculate winners
        $result = $this->aoqService->calculateWinnersAndTies($this->pr);

        // Assert
        $itemResult = $result[$this->prItem->id];

        $this->assertTrue($itemResult['has_tie'], 'Should have a tie');
        $this->assertEquals(900.00, $itemResult['lowest_price']);

        // Check that tied items are marked correctly
        $quotes = QuotationItem::where('purchase_request_item_id', $this->prItem->id)
            ->where('is_tied', true)
            ->get();

        $this->assertCount(2, $quotes, 'Should have 2 tied quotes');

        // No winner should be assigned yet
        $winners = QuotationItem::where('purchase_request_item_id', $this->prItem->id)
            ->where('is_winner', true)
            ->count();

        $this->assertEquals(0, $winners, 'No winner should be assigned for tied items');
    }

    /** @test */
    public function it_resolves_tie_correctly()
    {
        // Create tied quotations
        $quotation1 = $this->createQuotation($this->suppliers[0], 90.00);
        $quotation2 = $this->createQuotation($this->suppliers[1], 90.00);

        $quoteItem1 = $quotation1->quotationItems->first();
        $quoteItem2 = $quotation2->quotationItems->first();

        // Calculate winners (will detect tie)
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Resolve tie in favor of supplier A
        $decision = $this->aoqService->resolveTie(
            $this->pr,
            $this->prItem->id,
            $quoteItem1->id,
            'Supplier A has better delivery terms',
            $this->bacUser
        );

        // Assert decision was created
        $this->assertInstanceOf(AoqItemDecision::class, $decision);
        $this->assertEquals('tie_resolution', $decision->decision_type);
        $this->assertEquals($this->bacUser->id, $decision->decided_by);
        $this->assertTrue($decision->is_active);

        // Assert winner is marked correctly
        $quoteItem1->refresh();
        $quoteItem2->refresh();

        $this->assertTrue($quoteItem1->is_winner);
        $this->assertFalse($quoteItem2->is_winner);
    }

    /** @test */
    public function it_applies_bac_override_correctly()
    {
        // Create quotations where supplier A is lowest
        $quotation1 = $this->createQuotation($this->suppliers[0], 90.00);  // Lowest
        $quotation2 = $this->createQuotation($this->suppliers[1], 95.00);

        $quoteItem1 = $quotation1->quotationItems->first();
        $quoteItem2 = $quotation2->quotationItems->first();

        // Calculate winners (supplier A wins automatically)
        $this->aoqService->calculateWinnersAndTies($this->pr);

        $quoteItem1->refresh();
        $this->assertTrue($quoteItem1->is_winner);

        // Apply BAC override to change winner to supplier B
        $decision = $this->aoqService->applyBacOverride(
            $this->pr,
            $this->prItem->id,
            $quoteItem2->id,
            'Supplier B has better quality certification and local presence despite higher price',
            $this->bacUser
        );

        // Assert decision was created
        $this->assertEquals('bac_override', $decision->decision_type);
        $this->assertFalse($decision->isAutomatic());
        $this->assertTrue($decision->isBacOverride());

        // Assert winner changed
        $quoteItem1->refresh();
        $quoteItem2->refresh();

        $this->assertFalse($quoteItem1->is_winner);
        $this->assertTrue($quoteItem2->is_winner);
    }

    /** @test */
    public function it_tracks_multiple_decisions_for_same_item()
    {
        // Create tied quotations
        $quotation1 = $this->createQuotation($this->suppliers[0], 90.00);
        $quotation2 = $this->createQuotation($this->suppliers[1], 90.00);

        $quoteItem1 = $quotation1->quotationItems->first();
        $quoteItem2 = $quotation2->quotationItems->first();

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // First decision: resolve tie
        $decision1 = $this->aoqService->resolveTie(
            $this->pr,
            $this->prItem->id,
            $quoteItem1->id,
            'Initial tie resolution',
            $this->bacUser
        );

        // Second decision: BAC override to change winner
        $decision2 = $this->aoqService->applyBacOverride(
            $this->pr,
            $this->prItem->id,
            $quoteItem2->id,
            'Changed decision based on new information',
            $this->bacUser
        );

        // Assert both decisions exist in database
        $allDecisions = AoqItemDecision::where('purchase_request_item_id', $this->prItem->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(2, $allDecisions);

        // Assert only latest is active
        $this->assertFalse($allDecisions[0]->is_active);
        $this->assertTrue($allDecisions[1]->is_active);

        // Assert correct winner
        $quoteItem2->refresh();
        $this->assertTrue($quoteItem2->is_winner);
    }

    /** @test */
    public function it_cannot_generate_aoq_with_unresolved_ties()
    {
        // Create tied quotations
        $this->createQuotation($this->suppliers[0], 90.00);
        $this->createQuotation($this->suppliers[1], 90.00);

        // Calculate winners (will detect tie but not resolve)
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Check if can generate
        $validation = $this->aoqService->canGenerateAoq($this->pr);

        $this->assertFalse($validation['can_generate']);
        $this->assertNotEmpty($validation['errors']);
        $this->assertStringContainsString('unresolved ties', implode(' ', $validation['errors']));
    }

    /** @test */
    public function it_can_generate_aoq_with_all_ties_resolved()
    {
        // Create tied quotations
        $quotation1 = $this->createQuotation($this->suppliers[0], 90.00);
        $this->createQuotation($this->suppliers[1], 90.00);

        $quoteItem1 = $quotation1->quotationItems->first();

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Resolve tie
        $this->aoqService->resolveTie(
            $this->pr,
            $this->prItem->id,
            $quoteItem1->id,
            'Tie resolved',
            $this->bacUser
        );

        // Check if can generate
        $validation = $this->aoqService->canGenerateAoq($this->pr);

        $this->assertTrue($validation['can_generate']);
        $this->assertEmpty($validation['errors']);
    }

    /** @test */
    public function it_assigns_correct_ranks_to_all_quotes()
    {
        // Create quotations with different prices
        $this->createQuotation($this->suppliers[0], 95.00);  // Rank 2
        $this->createQuotation($this->suppliers[1], 100.00); // Rank 3
        $this->createQuotation($this->suppliers[2], 90.00);  // Rank 1 (lowest)

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get all quotes ordered by rank
        $quotes = QuotationItem::where('purchase_request_item_id', $this->prItem->id)
            ->orderBy('rank')
            ->get();

        $this->assertEquals(1, $quotes[0]->rank);
        $this->assertEquals(900.00, $quotes[0]->total_price);

        $this->assertEquals(2, $quotes[1]->rank);
        $this->assertEquals(950.00, $quotes[1]->total_price);

        $this->assertEquals(3, $quotes[2]->rank);
        $this->assertEquals(1000.00, $quotes[2]->total_price);
    }

    /** @test */
    public function it_handles_multiple_items_independently()
    {
        // Create second PR item
        $prItem2 = PurchaseRequestItem::create([
            'purchase_request_id' => $this->pr->id,
            'item_name' => 'Test Item 2',
            'unit_of_measure' => 'BOX',
            'quantity_requested' => 5,
            'estimated_unit_cost' => 200.00,
            'estimated_total_cost' => 1000.00,
        ]);

        // For item 1: supplier A wins clearly
        $quotation1a = $this->createQuotation($this->suppliers[0], 90.00);
        $this->createQuotation($this->suppliers[1], 100.00);

        // For item 2: suppliers B and C tie
        $quotation2b = $this->createQuotation($this->suppliers[1], 180.00, $prItem2);
        $quotation2c = $this->createQuotation($this->suppliers[2], 180.00, $prItem2);

        // Calculate winners
        $result = $this->aoqService->calculateWinnersAndTies($this->pr);

        // Item 1 should have winner
        $this->assertFalse($result[$this->prItem->id]['has_tie']);
        $this->assertCount(1, $result[$this->prItem->id]['winners']);

        // Item 2 should have tie
        $this->assertTrue($result[$prItem2->id]['has_tie']);
        $this->assertCount(0, $result[$prItem2->id]['winners']);

        // Resolve item 2 tie
        $quoteItem2b = $quotation2b->quotationItems()->where('purchase_request_item_id', $prItem2->id)->first();
        $this->aoqService->resolveTie(
            $this->pr,
            $prItem2->id,
            $quoteItem2b->id,
            'Better delivery schedule',
            $this->bacUser
        );

        // Now should be able to generate AOQ
        $validation = $this->aoqService->canGenerateAoq($this->pr);
        $this->assertTrue($validation['can_generate']);
    }

    /**
     * Helper method to create a quotation with a quote item
     */
    protected function createQuotation(Supplier $supplier, float $unitPrice, ?PurchaseRequestItem $prItem = null): Quotation
    {
        $prItem = $prItem ?? $this->prItem;
        $quantity = $prItem->quantity_requested;
        $totalPrice = $unitPrice * $quantity;

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-'.$supplier->supplier_code,
            'purchase_request_id' => $this->pr->id,
            'supplier_id' => $supplier->id,
            'quotation_date' => now(),
            'validity_date' => now()->addDays(10),
            'total_amount' => $totalPrice,
            'bac_status' => 'pending_evaluation',
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'purchase_request_item_id' => $prItem->id,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'is_within_abc' => $unitPrice <= $prItem->estimated_unit_cost,
        ]);

        return $quotation->load('quotationItems');
    }
}
