<?php

namespace App\Console\Commands;

use Database\Seeders\CEOApprovalPurchaseRequestSeeder;
use Illuminate\Console\Command;

class SeedCEOApprovalPRs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:ceo-approval-prs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Purchase Requests that are awaiting CEO approval';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding Purchase Requests awaiting CEO approval...');

        $seeder = new CEOApprovalPurchaseRequestSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('CEO approval Purchase Requests seeded successfully!');

        return Command::SUCCESS;
    }
}
