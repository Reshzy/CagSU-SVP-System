<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Console\Command;

class ArchiveExistingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:archive-existing 
                            {--force : Force archive without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive all existing departments, users, and purchase requests for college system migration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🗂️  College System Migration - Archive Existing Data');
        $this->newLine();

        // Count records to be archived
        $deptCount = Department::where('is_archived', false)->count();
        $userCount = User::where('is_archived', false)->count();
        $prCount = PurchaseRequest::where('is_archived', false)->count();

        if ($deptCount === 0 && $userCount === 0 && $prCount === 0) {
            $this->info('✅ No data to archive. All records are already archived.');

            return self::SUCCESS;
        }

        $this->table(
            ['Entity', 'Count'],
            [
                ['Departments', $deptCount],
                ['Users', $userCount],
                ['Purchase Requests', $prCount],
            ]
        );

        $this->newLine();
        $this->warn('⚠️  This will archive all existing data to prepare for the college-based system.');
        $this->warn('   Archived data will remain in the database but will not appear in active queries.');
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('Do you want to continue?', false)) {
                $this->info('Operation cancelled.');

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('📦 Archiving data...');

        // Archive departments
        if ($deptCount > 0) {
            Department::where('is_archived', false)->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);
            $this->info('✓ Archived departments');
        }

        // Archive users
        if ($userCount > 0) {
            User::where('is_archived', false)->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);
            $this->info('✓ Archived users');
        }

        // Archive purchase requests
        if ($prCount > 0) {
            PurchaseRequest::where('is_archived', false)->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);
            $this->info('✓ Archived purchase requests');
        }

        $this->newLine();
        $this->info('✅ Archive complete!');
        $this->info("   - {$deptCount} departments archived");
        $this->info("   - {$userCount} users archived");
        $this->info("   - {$prCount} purchase requests archived");
        $this->newLine();
        $this->info('💡 You can now proceed with seeding the new college structure.');

        return self::SUCCESS;
    }
}
