<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OfficeSpecificPurchaseRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Purchase Requests for specific offices...');

        // Get or create the specific departments we need
        $budgetOffice = Department::firstOrCreate(
            ['name' => 'Budget Office'],
            [
                'code' => 'BUDGET',
                'description' => 'Budget Office - handles budget planning and allocation',
                'head_name' => 'Budget Officer',
                'contact_email' => 'budget@cagsu.edu.ph',
                'is_active' => true,
            ]
        );

        $executiveOffice = Department::firstOrCreate(
            ['name' => 'Executive Office'],
            [
                'code' => 'EXEC',
                'description' => 'Executive Office - handles executive decisions and approvals',
                'head_name' => 'Executive Officer',
                'contact_email' => 'executive@cagsu.edu.ph',
                'is_active' => true,
            ]
        );

        $accountingOffice = Department::firstOrCreate(
            ['name' => 'Accounting Office'],
            [
                'code' => 'ACCTG',
                'description' => 'Accounting Office - handles financial records and accounting',
                'head_name' => 'Chief Accountant',
                'contact_email' => 'accounting@cagsu.edu.ph',
                'is_active' => true,
            ]
        );

        // Get existing users or create some if needed
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Creating sample users...');
            $users = collect([
                User::factory()->create(['name' => 'Budget Officer', 'email' => 'budget.officer@cagsu.edu.ph', 'department_id' => $budgetOffice->id]),
                User::factory()->create(['name' => 'Executive Officer', 'email' => 'executive.officer@cagsu.edu.ph', 'department_id' => $executiveOffice->id]),
                User::factory()->create(['name' => 'Chief Accountant', 'email' => 'chief.accountant@cagsu.edu.ph', 'department_id' => $accountingOffice->id]),
            ]);
        }

        // Create 3 PRs for Budget Office
        $this->command->info('Creating 3 Purchase Requests for Budget Office...');
        $this->createOfficeSpecificPRs($budgetOffice, $users, 3, [
            'purposes' => [
                'Budget planning software and tools',
                'Office supplies for budget preparation',
                'Financial analysis equipment and software'
            ],
            'items' => [
                ['name' => 'Budget Planning Software License', 'category' => 'services', 'specs' => 'Annual license for budget planning software with multi-user access (5 users)'],
                ['name' => 'Financial Calculator HP 12C', 'category' => 'equipment', 'specs' => 'HP 12C Financial Calculator for budget calculations and analysis'],
                ['name' => 'Accounting Paper Green Bar', 'category' => 'office_supplies', 'specs' => 'Continuous form green bar paper for budget reports, 15" x 11"'],
                ['name' => 'Budget Binders A4', 'category' => 'office_supplies', 'specs' => 'Heavy duty 3-ring binders for budget documentation, 3-inch capacity'],
                ['name' => 'Excel Training Course', 'category' => 'services', 'specs' => 'Advanced Excel training for budget analysis and forecasting, 16-hour course'],
            ]
        ]);

        // Create 3 PRs for Executive Office
        $this->command->info('Creating 3 Purchase Requests for Executive Office...');
        $this->createOfficeSpecificPRs($executiveOffice, $users, 3, [
            'purposes' => [
                'Executive meeting and conference equipment',
                'Office furniture for executive meetings',
                'Communication and presentation systems'
            ],
            'items' => [
                ['name' => 'Executive Conference Table 8-seater', 'category' => 'furniture', 'specs' => '8-seater executive conference table, mahogany finish with cable management'],
                ['name' => 'Video Conferencing System Basic', 'category' => 'ict_equipment', 'specs' => 'HD video conferencing system with camera, wireless presentation, and audio'],
                ['name' => 'Executive Leather Chairs', 'category' => 'furniture', 'specs' => 'High-back executive chairs, leather upholstery, ergonomic design'],
                ['name' => 'Digital Display 43-inch', 'category' => 'ict_equipment', 'specs' => '43-inch 4K digital display for presentations and announcements'],
                ['name' => 'Document Management System', 'category' => 'services', 'specs' => 'Basic document management system for executive documents with security'],
            ]
        ]);

        // Create 3 PRs for Accounting Office
        $this->command->info('Creating 3 Purchase Requests for Accounting Office...');
        $this->createOfficeSpecificPRs($accountingOffice, $users, 3, [
            'purposes' => [
                'Accounting software and financial systems',
                'Audit and compliance equipment',
                'Financial record keeping supplies'
            ],
            'items' => [
                ['name' => 'QuickBooks Pro License', 'category' => 'services', 'specs' => 'QuickBooks Pro annual license for 3 users with basic reporting features'],
                ['name' => 'Document Scanner Desktop', 'category' => 'equipment', 'specs' => 'Desktop document scanner, 25 pages per minute, duplex scanning'],
                ['name' => 'Fireproof Filing Cabinet 2-drawer', 'category' => 'furniture', 'specs' => '2-drawer fireproof filing cabinet for financial records, legal size'],
                ['name' => 'Adding Machine with Tape', 'category' => 'equipment', 'specs' => 'Desktop adding machine with printing tape for financial calculations'],
                ['name' => 'Accounting Forms Package', 'category' => 'office_supplies', 'specs' => 'Pre-printed accounting forms: invoices, receipts, vouchers, and ledger sheets'],
                ['name' => 'Basic Audit Software', 'category' => 'services', 'specs' => 'Basic audit trail software for transaction tracking and compliance'],
            ]
        ]);

        $totalPRs = PurchaseRequest::count();
        $totalItems = PurchaseRequestItem::count();

        $this->command->info("Successfully created Purchase Requests for specific offices!");
        $this->command->info("Total PRs in database: {$totalPRs}");
        $this->command->info("Total items in database: {$totalItems}");

        // Display summary by department
        $this->command->info("\nPurchase Request Summary by Department:");
        $deptCounts = PurchaseRequest::join('departments', 'purchase_requests.department_id', '=', 'departments.id')
            ->selectRaw('departments.name, count(*) as count')
            ->groupBy('departments.name')
            ->pluck('count', 'name');

        foreach ($deptCounts as $dept => $count) {
            $this->command->info("  {$dept}: {$count}");
        }
    }

    private function createOfficeSpecificPRs($department, $users, $count, $config)
    {
        for ($i = 0; $i < $count; $i++) {
            // Create PR with office-specific purpose
            $purpose = $config['purposes'][$i] ?? $config['purposes'][0];

            // Vary the status for testing different scenarios
            $statuses = ['draft', 'submitted', 'budget_office_review', 'ceo_approval', 'bac_evaluation'];
            $status = $statuses[$i % count($statuses)];

            $prData = [
                'requester_id' => $users->random()->id,
                'department_id' => $department->id,
                'purpose' => $purpose,
                'status' => $status,
                'justification' => "Required for {$department->name} operations to improve efficiency and service delivery",
                'procurement_type' => $i % 2 == 0 ? 'equipment' : 'services',
                'priority' => $i == 0 ? 'high' : 'medium',
            ];

            // Add earmark_id for PRs that have passed budget
            if (in_array($status, ['ceo_approval', 'bac_evaluation'])) {
                $prData['earmark_id'] = 'EAR-2025-' . str_pad(rand(1, 100), 4, '0', STR_PAD_LEFT);
            }

            // Add resolution_number and procurement_method for PRs at BAC
            if ($status === 'bac_evaluation') {
                $prData['resolution_number'] = 'RES-2025-' . str_pad(rand(1, 100), 4, '0', STR_PAD_LEFT);
                $prData['procurement_method'] = 'small_value_procurement';
            }

            $pr = PurchaseRequest::factory()->create($prData);

            // Add 2-4 items per PR from the office-specific items
            $itemCount = rand(2, 4);
            $selectedItems = collect($config['items'])->random($itemCount);

            foreach ($selectedItems as $itemData) {
                PurchaseRequestItem::factory()
                    ->create([
                        'purchase_request_id' => $pr->id,
                        'item_name' => $itemData['name'],
                        'detailed_specifications' => $itemData['specs'],
                        'item_category' => $itemData['category'],
                        'quantity_requested' => rand(1, 5),
                        'estimated_unit_cost' => $this->getEstimatedCost($itemData['category']),
                        'estimated_total_cost' => function ($attributes) {
                            return $attributes['quantity_requested'] * $attributes['estimated_unit_cost'];
                        },
                    ]);
            }
        }
    }

    private function getEstimatedCost($category)
    {
        $costs = [
            'office_supplies' => [50, 500],
            'equipment' => [500, 2500], // Reduced for small value
            'furniture' => [1000, 5000], // Reduced for small value
            'services' => [2000, 15000], // Reduced for small value
            'ict_equipment' => [1000, 8000], // Reduced for small value
        ];

        $range = $costs[$category] ?? [100, 1000];
        return fake()->randomFloat(2, $range[0], $range[1]);
    }
}
