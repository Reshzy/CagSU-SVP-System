<?php

namespace App\Console\Commands;

use Database\Seeders\OfficeSpecificPurchaseRequestSeeder;
use Illuminate\Console\Command;

class SeedOfficeSpecificPRs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:office-prs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Purchase Requests for Budget Office, Executive Office, and Accounting Office';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding office-specific purchase requests...');

        $seeder = new OfficeSpecificPurchaseRequestSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('Office-specific purchase requests seeded successfully!');

        return Command::SUCCESS;
    }
}
