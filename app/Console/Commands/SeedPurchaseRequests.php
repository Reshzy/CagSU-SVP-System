<?php

namespace App\Console\Commands;

use Database\Seeders\PurchaseRequestSeeder;
use Illuminate\Console\Command;

class SeedPurchaseRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:purchase-requests {--fresh : Clear existing purchase requests before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with sample purchase requests and items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('fresh')) {
            $this->warn('Clearing existing purchase requests...');
            \App\Models\PurchaseRequestItem::query()->delete();
            \App\Models\PurchaseRequest::query()->delete();
        }

        $this->info('Seeding purchase requests...');

        $seeder = new PurchaseRequestSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('Purchase requests seeded successfully!');

        return Command::SUCCESS;
    }
}
