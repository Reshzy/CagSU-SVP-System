<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have some users and departments to work with
        $users = User::all();
        $departments = Department::all();

        if ($users->isEmpty() || $departments->isEmpty()) {
            $this->command->warn('No users or departments found. Creating some basic data first...');

            // Create some departments if none exist
            if ($departments->isEmpty()) {
                $departments = collect([
                    Department::create([
                        'name' => 'Information Technology',
                        'code' => 'IT',
                        'description' => 'IT Department',
                        'head_name' => 'IT Head',
                        'contact_email' => 'it@cagsu.edu.ph',
                        'is_active' => true,
                    ]),
                    Department::create([
                        'name' => 'Human Resources',
                        'code' => 'HR',
                        'description' => 'HR Department',
                        'head_name' => 'HR Head',
                        'contact_email' => 'hr@cagsu.edu.ph',
                        'is_active' => true,
                    ]),
                    Department::create([
                        'name' => 'Finance',
                        'code' => 'FIN',
                        'description' => 'Finance Department',
                        'head_name' => 'Finance Head',
                        'contact_email' => 'finance@cagsu.edu.ph',
                        'is_active' => true,
                    ]),
                ]);
            }

            // Create some users if none exist
            if ($users->isEmpty()) {
                $users = collect([
                    User::factory()->create([
                        'name' => 'John Doe',
                        'email' => 'john.doe@cagsu.edu.ph',
                        'department_id' => $departments->first()->id,
                        'position' => 'IT Officer',
                        'is_active' => true,
                    ]),
                    User::factory()->create([
                        'name' => 'Jane Smith',
                        'email' => 'jane.smith@cagsu.edu.ph',
                        'department_id' => $departments->skip(1)->first()->id,
                        'position' => 'HR Officer',
                        'is_active' => true,
                    ]),
                    User::factory()->create([
                        'name' => 'Bob Johnson',
                        'email' => 'bob.johnson@cagsu.edu.ph',
                        'department_id' => $departments->last()->id,
                        'position' => 'Finance Officer',
                        'is_active' => true,
                    ]),
                ]);
            }
        }

        $this->command->info('Creating Purchase Requests with items...');

        // Create different types of purchase requests

        // Since we already have 6 PRs, we need 9 more to reach 15 total
        // Let's create: 3+2+2+2 = 9 more PRs

        // 1. Draft Purchase Requests (3)
        $this->command->info('Creating draft purchase requests...');
        for ($i = 0; $i < 3; $i++) {
            $pr = PurchaseRequest::factory()
                ->draft()
                ->create([
                    'requester_id' => $users->random()->id,
                    'department_id' => $departments->random()->id,
                ]);

            PurchaseRequestItem::factory()
                ->count(rand(1, 5))
                ->create(['purchase_request_id' => $pr->id]);
        }

        // 2. Recently Submitted Purchase Requests (2)
        $this->command->info('Creating submitted purchase requests...');
        for ($i = 0; $i < 2; $i++) {
            $pr = PurchaseRequest::factory()
                ->submitted()
                ->create([
                    'requester_id' => $users->random()->id,
                    'department_id' => $departments->random()->id,
                ]);

            PurchaseRequestItem::factory()
                ->count(rand(2, 6))
                ->create(['purchase_request_id' => $pr->id]);
        }

        // 3. Completed Purchase Requests (2)
        $this->command->info('Creating completed purchase requests...');
        for ($i = 0; $i < 2; $i++) {
            $pr = PurchaseRequest::factory()
                ->completed()
                ->create([
                    'requester_id' => $users->random()->id,
                    'department_id' => $departments->random()->id,
                ]);

            PurchaseRequestItem::factory()
                ->count(rand(1, 4))
                ->approved()
                ->create(['purchase_request_id' => $pr->id]);
        }

        // 4. Equipment Purchase Requests (2)
        $this->command->info('Creating equipment purchase requests...');
        for ($i = 0; $i < 2; $i++) {
            $pr = PurchaseRequest::factory()
                ->create([
                    'requester_id' => $users->random()->id,
                    'department_id' => $departments->random()->id,
                    'procurement_type' => 'equipment',
                ]);

            PurchaseRequestItem::factory()
                ->count(rand(1, 3))
                ->equipment()
                ->create(['purchase_request_id' => $pr->id]);
        }

        // Total: 3+2+2+2 = 9 new PRs (plus 6 existing = 15 total)

        $totalPRs = PurchaseRequest::count();
        $totalItems = PurchaseRequestItem::count();

        $this->command->info("Successfully created {$totalPRs} Purchase Requests with {$totalItems} items!");
        $this->command->info('Purchase Request seeding completed.');

        // Display summary by status
        $this->command->info("\nPurchase Request Summary by Status:");
        $statusCounts = PurchaseRequest::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        foreach ($statusCounts as $status => $count) {
            $this->command->info("  {$status}: {$count}");
        }
    }
}
