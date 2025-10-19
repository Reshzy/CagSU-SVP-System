<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CEOApprovalPurchaseRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Purchase Requests awaiting CEO approval...');

        // Get existing users and departments
        $users = User::all();
        $departments = Department::all();

        if ($users->isEmpty() || $departments->isEmpty()) {
            $this->command->warn('Insufficient users or departments. Please run the main seeders first.');
            return;
        }

        // Create PRs that require CEO approval (small value but strategic items)
        $ceoApprovalScenarios = [
            [
                'purpose' => 'Critical office equipment upgrade',
                'justification' => 'Essential office equipment upgrade required for improved productivity and service delivery',
                'estimated_total' => 45000.00,
                'procurement_type' => 'equipment',
                'procurement_method' => 'small_value_procurement',
                'priority' => 'high',
                'items' => [
                    ['name' => 'Multi-function Printer Canon', 'specs' => 'Canon imageRUNNER ADVANCE series, A3 capability, duplex printing, network ready', 'category' => 'ict_equipment', 'qty' => 2, 'unit_cost' => 15000],
                    ['name' => 'Document Scanner Fujitsu', 'specs' => 'High-speed document scanner, 40 pages per minute, duplex scanning with OCR', 'category' => 'ict_equipment', 'qty' => 1, 'unit_cost' => 15000],
                ]
            ],
            [
                'purpose' => 'Training and development program',
                'justification' => 'Staff development training program to enhance skills and productivity across departments',
                'estimated_total' => 35000.00,
                'procurement_type' => 'services',
                'procurement_method' => 'small_value_procurement',
                'priority' => 'medium',
                'items' => [
                    ['name' => 'Professional Development Training', 'specs' => 'Comprehensive training program for 20 staff members, 40-hour course with certification', 'category' => 'services', 'qty' => 1, 'unit_cost' => 25000],
                    ['name' => 'Training Materials Package', 'specs' => 'Complete training materials, workbooks, and reference guides for participants', 'category' => 'office_supplies', 'qty' => 20, 'unit_cost' => 500],
                ]
            ],
            [
                'purpose' => 'Security enhancement equipment',
                'justification' => 'Security equipment upgrade to enhance campus safety and monitoring capabilities',
                'estimated_total' => 48000.00,
                'procurement_type' => 'equipment',
                'procurement_method' => 'small_value_procurement',
                'priority' => 'high',
                'items' => [
                    ['name' => 'IP Security Cameras', 'specs' => '2MP IP cameras with night vision, weather-resistant housing, PoE powered', 'category' => 'equipment', 'qty' => 8, 'unit_cost' => 4500],
                    ['name' => 'Network Video Recorder', 'specs' => '16-channel NVR with 4TB storage, remote monitoring capability', 'category' => 'ict_equipment', 'qty' => 1, 'unit_cost' => 12000],
                ]
            ],
            [
                'purpose' => 'Laboratory supplies and equipment',
                'justification' => 'Essential laboratory supplies and basic equipment to support academic programs',
                'estimated_total' => 42000.00,
                'procurement_type' => 'equipment',
                'procurement_method' => 'small_value_procurement',
                'priority' => 'medium',
                'items' => [
                    ['name' => 'Digital Microscope Basic', 'specs' => 'Digital microscope with camera, 1000x magnification, LED illumination', 'category' => 'equipment', 'qty' => 3, 'unit_cost' => 12000],
                    ['name' => 'Laboratory Glassware Set', 'specs' => 'Complete laboratory glassware set including beakers, flasks, and measuring tools', 'category' => 'materials', 'qty' => 2, 'unit_cost' => 3000],
                ]
            ],
            [
                'purpose' => 'IT infrastructure maintenance',
                'justification' => 'Critical IT infrastructure maintenance and minor upgrades for system reliability',
                'estimated_total' => 38000.00,
                'procurement_type' => 'services',
                'procurement_method' => 'small_value_procurement',
                'priority' => 'high',
                'items' => [
                    ['name' => 'Network Equipment Maintenance', 'specs' => 'Annual maintenance service for network switches, routers, and access points', 'category' => 'services', 'qty' => 1, 'unit_cost' => 25000],
                    ['name' => 'UPS Battery Replacement', 'specs' => 'Replacement batteries for UPS systems, 12V sealed lead acid batteries', 'category' => 'equipment', 'qty' => 10, 'unit_cost' => 1300],
                ]
            ],
            [
                'purpose' => 'Office furniture and supplies',
                'justification' => 'Office furniture and supplies for new staff and workspace improvements',
                'estimated_total' => 49500.00,
                'procurement_type' => 'furniture',
                'procurement_method' => 'small_value_procurement',
                'priority' => 'medium',
                'items' => [
                    ['name' => 'Executive Office Chair', 'specs' => 'Ergonomic executive chair with leather upholstery and adjustable features', 'category' => 'furniture', 'qty' => 5, 'unit_cost' => 7500],
                    ['name' => 'Office Desk 4ft', 'specs' => '4-foot office desk with drawers, laminated wood top, metal frame', 'category' => 'furniture', 'qty' => 3, 'unit_cost' => 4500],
                    ['name' => 'Filing Cabinet 4-drawer', 'specs' => '4-drawer filing cabinet, legal size, lockable, powder-coated steel', 'category' => 'furniture', 'qty' => 2, 'unit_cost' => 3500],
                ]
            ]
        ];

        foreach ($ceoApprovalScenarios as $index => $scenario) {
            $prNumber = $index + 1;
            $this->command->info("Creating CEO approval PR #{$prNumber}: {$scenario['purpose']}");

            // Create the Purchase Request
            $pr = PurchaseRequest::factory()->create([
                'requester_id' => $users->random()->id,
                'department_id' => $departments->random()->id,
                'purpose' => $scenario['purpose'],
                'justification' => $scenario['justification'],
                'estimated_total' => $scenario['estimated_total'],
                'procurement_type' => $scenario['procurement_type'],
                'procurement_method' => $scenario['procurement_method'],
                'priority' => $scenario['priority'],
                'status' => 'ceo_approval',
                'current_handler_id' => $users->random()->id,
                'current_step_notes' => 'Budget office has completed earmarking. Awaiting CEO approval for procurement.',
                'submitted_at' => now()->subDays(rand(5, 15)),
                'status_updated_at' => now()->subDays(rand(1, 5)),
                'has_ppmp' => true,
                'ppmp_reference' => 'PPMP-2025-' . str_pad($prNumber, 3, '0', STR_PAD_LEFT),
            ]);

            // Create items for this PR
            foreach ($scenario['items'] as $itemData) {
                $totalCost = $itemData['qty'] * $itemData['unit_cost'];

                PurchaseRequestItem::factory()->create([
                    'purchase_request_id' => $pr->id,
                    'item_name' => $itemData['name'],
                    'detailed_specifications' => $itemData['specs'],
                    'item_category' => $itemData['category'],
                    'quantity_requested' => $itemData['qty'],
                    'estimated_unit_cost' => $itemData['unit_cost'],
                    'estimated_total_cost' => $totalCost,
                    'item_status' => 'approved', // Budget office already approved
                    'approved_budget' => $totalCost,
                    'is_available_locally' => $itemData['category'] === 'services' ? false : true,
                    'special_requirements' => $this->getSpecialRequirements($itemData['category']),
                ]);
            }
        }

        $totalCEOApprovalPRs = PurchaseRequest::where('status', 'ceo_approval')->count();
        $totalPRs = PurchaseRequest::count();
        $totalItems = PurchaseRequestItem::count();

        $this->command->info("Successfully created 6 Purchase Requests awaiting CEO approval!");
        $this->command->info("Total PRs awaiting CEO approval: {$totalCEOApprovalPRs}");
        $this->command->info("Total PRs in database: {$totalPRs}");
        $this->command->info("Total items in database: {$totalItems}");

        // Display the CEO approval PRs
        $this->command->info("\nPurchase Requests Awaiting CEO Approval:");
        $ceoApprovalPRs = PurchaseRequest::where('status', 'ceo_approval')
            ->with('department')
            ->get();

        foreach ($ceoApprovalPRs as $pr) {
            $this->command->info("  {$pr->pr_number} - {$pr->purpose} (₱" . number_format($pr->estimated_total, 2) . ") - {$pr->department->name}");
        }
    }

    private function getSpecialRequirements($category)
    {
        $requirements = [
            'ict_equipment' => [
                'Installation and configuration required',
                'Technical support and training included',
                'Warranty and maintenance agreement required',
                'Compatibility testing with existing systems'
            ],
            'equipment' => [
                'Professional installation required',
                'Operator training included',
                'Maintenance manual and spare parts',
                'Compliance with safety standards'
            ],
            'infrastructure' => [
                'Site survey and preparation required',
                'Professional installation and commissioning',
                'Building permits and compliance certificates',
                'Performance testing and documentation'
            ],
            'services' => [
                'Certified and licensed service provider',
                'Performance guarantee and SLA',
                'Regular progress reporting',
                'Quality assurance and testing'
            ]
        ];

        $categoryRequirements = $requirements[$category] ?? ['Standard delivery and setup'];
        return fake()->randomElement($categoryRequirements);
    }
}
