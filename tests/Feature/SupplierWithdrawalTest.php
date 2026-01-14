<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\SupplierWithdrawal;
use App\Models\User;
use App\Services\AoqService;
use App\Services\SupplierWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    protected AoqService $aoqService;

    protected SupplierWithdrawalService $withdrawalService;

    protected User $bacUser;

    protected PurchaseRequest $pr;

    protected PurchaseRequestItem $prItem;

    protected array $suppliers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aoqService = new AoqService;
        $this->withdrawalService = new SupplierWithdrawalService($this->aoqService);

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
            'procurement_status' => 'pending',
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
    public function it_can_withdraw_a_winning_quotation_item()
    {
        // Create quotations with different prices
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);  // Lowest - will be winner
        $this->createQuotation($this->suppliers[1], 95.00);
        $this->createQuotation($this->suppliers[2], 100.00);

        // Calculate winners to set up the winning item
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get the winning quotation item
        $winningItem = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // Verify it's the winner
        $this->assertTrue($winningItem->is_winner);

        // Withdraw the winning item
        $result = $this->withdrawalService->withdraw($winningItem, 'Supplier cannot fulfill order', $this->bacUser);

        // Assert withdrawal was successful
        $this->assertTrue($result['success']);
        $this->assertTrue($result['has_successor']);

        // Refresh and verify the item is now withdrawn
        $winningItem->refresh();
        $this->assertTrue($winningItem->is_withdrawn);
        $this->assertFalse($winningItem->is_winner);
        $this->assertEquals('Supplier cannot fulfill order', $winningItem->withdrawal_reason);
    }

    /** @test */
    public function it_promotes_next_bidder_after_withdrawal()
    {
        // Create quotations with different prices
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);  // Lowest - will be winner
        $quotationB = $this->createQuotation($this->suppliers[1], 95.00);  // Second lowest
        $this->createQuotation($this->suppliers[2], 100.00);

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get the winning quotation item
        $winningItem = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // Get the second bidder's item
        $secondItem = QuotationItem::where('quotation_id', $quotationB->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // Verify initial state
        $this->assertTrue($winningItem->is_winner);
        $this->assertFalse($secondItem->is_winner);

        // Withdraw the winning item
        $result = $this->withdrawalService->withdraw($winningItem, 'Supplier cannot fulfill order', $this->bacUser);

        // Assert successor was identified
        $this->assertTrue($result['has_successor']);
        $this->assertEquals($secondItem->id, $result['successor']->id);

        // Refresh and verify the second bidder is now the winner
        $secondItem->refresh();
        $this->assertTrue($secondItem->is_winner);
    }

    /** @test */
    public function it_creates_withdrawal_record()
    {
        // Create quotations
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);
        $this->createQuotation($this->suppliers[1], 95.00);

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get the winning quotation item
        $winningItem = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // Withdraw
        $this->withdrawalService->withdraw($winningItem, 'Test withdrawal reason', $this->bacUser);

        // Assert withdrawal record was created
        $withdrawal = SupplierWithdrawal::where('quotation_item_id', $winningItem->id)->first();
        $this->assertNotNull($withdrawal);
        $this->assertEquals($this->suppliers[0]->id, $withdrawal->supplier_id);
        $this->assertEquals($this->prItem->id, $withdrawal->purchase_request_item_id);
        $this->assertEquals('Test withdrawal reason', $withdrawal->withdrawal_reason);
        $this->assertEquals($this->bacUser->id, $withdrawal->withdrawn_by);
        $this->assertFalse($withdrawal->resulted_in_failure);
    }

    /** @test */
    public function it_marks_item_as_failed_when_no_successors()
    {
        // Create only one quotation
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get the winning quotation item
        $winningItem = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // Withdraw
        $result = $this->withdrawalService->withdraw($winningItem, 'Supplier cannot fulfill order', $this->bacUser);

        // Assert no successor and failure
        $this->assertFalse($result['has_successor']);
        $this->assertNull($result['successor']);

        // Verify PR item is marked as failed
        $this->prItem->refresh();
        $this->assertEquals('failed', $this->prItem->procurement_status);
    }

    /** @test */
    public function it_records_failure_in_withdrawal_record()
    {
        // Create only one quotation
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get the winning quotation item
        $winningItem = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // Withdraw
        $this->withdrawalService->withdraw($winningItem, 'Supplier cannot fulfill order', $this->bacUser);

        // Assert withdrawal record shows failure
        $withdrawal = SupplierWithdrawal::where('quotation_item_id', $winningItem->id)->first();
        $this->assertTrue($withdrawal->resulted_in_failure);
        $this->assertNull($withdrawal->successor_quotation_item_id);
    }

    /** @test */
    public function it_cannot_withdraw_non_winning_item()
    {
        // Create quotations
        $this->createQuotation($this->suppliers[0], 90.00);  // Winner
        $quotationB = $this->createQuotation($this->suppliers[1], 95.00);  // Not winner

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get the non-winning quotation item
        $nonWinningItem = QuotationItem::where('quotation_id', $quotationB->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // Attempt to withdraw
        $result = $this->withdrawalService->withdraw($nonWinningItem, 'Test reason', $this->bacUser);

        // Assert failure
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('winning', strtolower($result['message']));
    }

    /** @test */
    public function it_cannot_withdraw_already_withdrawn_item()
    {
        // Create quotations
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);
        $this->createQuotation($this->suppliers[1], 95.00);

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get the winning quotation item
        $winningItem = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // First withdrawal
        $this->withdrawalService->withdraw($winningItem, 'First withdrawal', $this->bacUser);

        // Attempt second withdrawal
        $winningItem->refresh();
        $result = $this->withdrawalService->withdraw($winningItem, 'Second withdrawal', $this->bacUser);

        // Assert failure
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('withdrawn', strtolower($result['message']));
    }

    /** @test */
    public function it_handles_multiple_withdrawals_in_sequence()
    {
        // Create quotations with different prices
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);  // First winner
        $quotationB = $this->createQuotation($this->suppliers[1], 95.00);  // Second winner after first withdrawal
        $quotationC = $this->createQuotation($this->suppliers[2], 100.00); // Third winner after second withdrawal

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get items
        $itemA = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();
        $itemB = QuotationItem::where('quotation_id', $quotationB->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();
        $itemC = QuotationItem::where('quotation_id', $quotationC->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // First withdrawal
        $result1 = $this->withdrawalService->withdraw($itemA, 'First withdrawal', $this->bacUser);
        $this->assertTrue($result1['success']);
        $this->assertTrue($result1['has_successor']);

        // Verify B is now winner
        $itemB->refresh();
        $this->assertTrue($itemB->is_winner);

        // Second withdrawal
        $result2 = $this->withdrawalService->withdraw($itemB, 'Second withdrawal', $this->bacUser);
        $this->assertTrue($result2['success']);
        $this->assertTrue($result2['has_successor']);

        // Verify C is now winner
        $itemC->refresh();
        $this->assertTrue($itemC->is_winner);

        // Third withdrawal - should result in failure
        $result3 = $this->withdrawalService->withdraw($itemC, 'Third withdrawal', $this->bacUser);
        $this->assertTrue($result3['success']);
        $this->assertFalse($result3['has_successor']);

        // Verify PR item is failed
        $this->prItem->refresh();
        $this->assertEquals('failed', $this->prItem->procurement_status);
    }

    /** @test */
    public function it_skips_disqualified_bidders_for_succession()
    {
        // Create quotations
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);  // Winner
        $quotationB = $this->createQuotation($this->suppliers[1], 95.00);  // Disqualified
        $quotationC = $this->createQuotation($this->suppliers[2], 100.00); // Should become winner

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Disqualify supplier B
        $itemB = QuotationItem::where('quotation_id', $quotationB->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();
        $itemB->update(['disqualification_reason' => 'Failed compliance check']);

        // Get winner and withdraw
        $itemA = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        $result = $this->withdrawalService->withdraw($itemA, 'Withdrawal test', $this->bacUser);

        // Assert C became the winner (skipping disqualified B)
        $itemC = QuotationItem::where('quotation_id', $quotationC->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();
        $itemC->refresh();

        $this->assertTrue($result['has_successor']);
        $this->assertEquals($itemC->id, $result['successor']->id);
        $this->assertTrue($itemC->is_winner);
    }

    /** @test */
    public function it_can_check_if_withdrawal_would_cause_failure()
    {
        // Create only one quotation
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get the winning quotation item
        $winningItem = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // Check if withdrawal would cause failure
        $wouldCauseFailure = $this->withdrawalService->wouldCauseFailure($winningItem);

        $this->assertTrue($wouldCauseFailure);
    }

    /** @test */
    public function it_can_preview_next_bidder()
    {
        // Create quotations
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);
        $quotationB = $this->createQuotation($this->suppliers[1], 95.00);

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Get the winning quotation item
        $winningItem = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();

        // Get preview
        $preview = $this->withdrawalService->getNextBidderPreview($winningItem);

        $this->assertNotNull($preview);
        $this->assertEquals('Supplier B', $preview['supplier_name']);
        $this->assertEquals(95.00, $preview['unit_price']);
    }

    /** @test */
    public function it_tracks_withdrawal_history()
    {
        // Create quotations
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);
        $quotationB = $this->createQuotation($this->suppliers[1], 95.00);

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Withdraw first winner
        $itemA = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();
        $this->withdrawalService->withdraw($itemA, 'First withdrawal', $this->bacUser);

        // Withdraw second winner
        $itemB = QuotationItem::where('quotation_id', $quotationB->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();
        $itemB->refresh();
        $this->withdrawalService->withdraw($itemB, 'Second withdrawal', $this->bacUser);

        // Get withdrawal history
        $history = $this->withdrawalService->getWithdrawalHistory($this->pr);

        $this->assertCount(2, $history);
    }

    /** @test */
    public function it_provides_withdrawal_summary()
    {
        // Create quotations
        $quotationA = $this->createQuotation($this->suppliers[0], 90.00);
        $quotationB = $this->createQuotation($this->suppliers[1], 95.00);

        // Calculate winners
        $this->aoqService->calculateWinnersAndTies($this->pr);

        // Withdraw first winner
        $itemA = QuotationItem::where('quotation_id', $quotationA->id)
            ->where('purchase_request_item_id', $this->prItem->id)
            ->first();
        $this->withdrawalService->withdraw($itemA, 'First withdrawal', $this->bacUser);

        // Get summary
        $summary = $this->withdrawalService->getWithdrawalSummary($this->pr);

        $this->assertEquals(1, $summary['total_withdrawals']);
        $this->assertEquals(1, $summary['withdrawals_with_successors']);
        $this->assertEquals(0, $summary['withdrawals_causing_failure']);
    }

    /**
     * Helper method to create a quotation with a specific unit price
     */
    protected function createQuotation(Supplier $supplier, float $unitPrice): Quotation
    {
        $quotation = Quotation::create([
            'purchase_request_id' => $this->pr->id,
            'supplier_id' => $supplier->id,
            'quotation_number' => 'QN-'.time().'-'.$supplier->id,
            'quotation_date' => now(),
            'validity_date' => now()->addDays(10),
            'grand_total' => $unitPrice * $this->prItem->quantity_requested,
            'is_within_abc' => $unitPrice <= $this->prItem->estimated_unit_cost,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'purchase_request_item_id' => $this->prItem->id,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $this->prItem->quantity_requested,
            'is_within_abc' => $unitPrice <= $this->prItem->estimated_unit_cost,
            'is_withdrawn' => false,
        ]);

        return $quotation;
    }
}
