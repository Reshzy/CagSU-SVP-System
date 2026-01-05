<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignRolesToApprovedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:assign-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign roles to approved users based on their positions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Assigning roles to approved users...');

        // Get all approved users
        $users = User::with('position')
            ->where('approval_status', 'approved')
            ->where('is_active', true)
            ->get();

        $positionRoleMap = [
            'System Administrator' => 'System Admin',
            'Supply Officer' => 'Supply Officer',
            'Budget Officer' => 'Budget Office',
            'Executive Officer' => 'Executive Officer',
            'BAC Chairman' => 'BAC Chair',
            'BAC Member' => 'BAC Members',
            'BAC Secretary' => 'BAC Secretariat',
            'Accounting Officer' => 'Accounting Office',
            'Canvassing Officer' => 'Canvassing Unit',
            'Dean' => 'Dean',
            'Employee' => 'End User',
        ];

        $updated = 0;
        $skipped = 0;

        foreach ($users as $user) {
            // Skip if user already has roles
            if ($user->roles->isNotEmpty()) {
                $this->line("Skipping {$user->name} - already has role(s): ".$user->roles->pluck('name')->join(', '));
                $skipped++;

                continue;
            }

            $positionName = $user->position?->name;
            $roleName = $positionName ? ($positionRoleMap[$positionName] ?? 'End User') : 'End User';

            $user->syncRoles([$roleName]);
            $this->info("✓ Assigned '{$roleName}' role to {$user->name} (Position: {$positionName})");
            $updated++;
        }

        $this->newLine();
        $this->info("✓ Complete! Updated: {$updated}, Skipped: {$skipped}");

        return Command::SUCCESS;
    }
}
