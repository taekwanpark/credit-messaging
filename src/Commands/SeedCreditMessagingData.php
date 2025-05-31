<?php

namespace Techigh\CreditMessaging\Commands;

use Illuminate\Console\Command;
use Techigh\CreditMessaging\Database\Seeders\CreditMessagingSeeder;

class SeedCreditMessagingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credit-messaging:seed {--fresh : Clear existing data before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed credit messaging system with dummy data for testing and demonstration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Credit Messaging System Data Seeding');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  This will delete all existing credit messaging data!');

            if (!$this->confirm('Do you want to continue?')) {
                $this->info('❌ Operation cancelled.');
                return Command::FAILURE;
            }

            $this->info('🧹 Clearing existing data...');
            $this->clearExistingData();
        }

        $this->info('📦 Seeding dummy data...');
        $this->call('db:seed', ['--class' => CreditMessagingSeeder::class]);

        $this->newLine();
        $this->info('✅ Credit messaging system has been seeded successfully!');
        $this->newLine();

        $this->displaySeededData();

        return Command::SUCCESS;
    }

    private function clearExistingData(): void
    {
        \Techigh\CreditMessaging\Models\MessageSendLog::truncate();
        \Techigh\CreditMessaging\Models\SiteCreditUsage::truncate();
        \Techigh\CreditMessaging\Models\CreditMessage::truncate();
        \Techigh\CreditMessaging\Models\SiteCreditPayment::truncate();
        \Techigh\CreditMessaging\Models\SiteCredit::truncate();

        $this->info('✓ Existing data cleared');
    }

    private function displaySeededData(): void
    {
        $siteCredits = \Techigh\CreditMessaging\Models\SiteCredit::count();
        $payments = \Techigh\CreditMessaging\Models\SiteCreditPayment::count();
        $messages = \Techigh\CreditMessaging\Models\CreditMessage::count();
        $usages = \Techigh\CreditMessaging\Models\SiteCreditUsage::count();
        $sendLogs = \Techigh\CreditMessaging\Models\MessageSendLog::count();

        $this->table(
            ['Entity', 'Count', 'Description'],
            [
                ['Site Credits', $siteCredits, 'Credit accounts for different sites'],
                ['Payments', $payments, 'Payment and charging records'],
                ['Messages', $messages, 'Credit message campaigns'],
                ['Usages', $usages, 'Credit usage tracking'],
                ['Send Logs', $sendLogs, 'Message delivery logs'],
            ]
        );

        $this->newLine();
        $this->info('📋 Sample Data Created:');
        $this->line('• demo_site_001: High balance with auto-charge enabled');
        $this->line('• demo_site_002: Medium balance with completed campaigns');
        $this->line('• demo_site_003: Low balance for testing scenarios');
        $this->newLine();
        $this->info('🔗 Access the admin panel to view and manage the data:');
        $this->line('• Credit Messages: /settings/credit-messages');
        $this->line('• Site Credits: /settings/site-credits');
        $this->line('• Payments: /settings/site-credit-payments');
    }
}
