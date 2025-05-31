<?php

namespace Techigh\CreditMessaging\Database\Seeders;

use Illuminate\Database\Seeder;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\CreditMessage;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\MessageSendLog;

class CreditMessagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Credit Messaging System seeding...');

        // 1. Create Site Credits for different sites
        $this->createSiteCredits();

        // 2. Create Payment records
        $this->createPayments();

        // 3. Create Credit Messages
        $this->createCreditMessages();

        // 4. Create Usage records
        $this->createUsageRecords();

        // 5. Create Send Logs
        $this->createSendLogs();

        $this->command->info('✅ Credit Messaging System seeding completed!');
    }

    private function createSiteCredits(): void
    {
        $this->command->info('📊 Creating Site Credits...');

        $sites = [
            [
                'site_id' => 'demo_site_001',
                'balance' => 150000.00,
                'alimtalk_cost' => 15.00,
                'sms_cost' => 20.00,
                'lms_cost' => 50.00,
                'mms_cost' => 200.00,
                'auto_charge_enabled' => true,
                'auto_charge_threshold' => 10000.00,
                'auto_charge_amount' => 50000.00,
            ],
            [
                'site_id' => 'demo_site_002',
                'balance' => 75000.00,
                'alimtalk_cost' => 12.00,
                'sms_cost' => 18.00,
                'lms_cost' => 45.00,
                'mms_cost' => 180.00,
                'auto_charge_enabled' => false,
                'auto_charge_threshold' => null,
                'auto_charge_amount' => null,
            ],
            [
                'site_id' => 'demo_site_003',
                'balance' => 5000.00, // Low balance for testing
                'alimtalk_cost' => 20.00,
                'sms_cost' => 25.00,
                'lms_cost' => 60.00,
                'mms_cost' => 250.00,
                'auto_charge_enabled' => true,
                'auto_charge_threshold' => 20000.00,
                'auto_charge_amount' => 100000.00,
            ],
        ];

        foreach ($sites as $siteData) {
            SiteCredit::updateOrCreate(
                ['site_id' => $siteData['site_id']],
                $siteData
            );
        }

        $this->command->info('✓ Created ' . count($sites) . ' site credits');
    }

    private function createPayments(): void
    {
        $this->command->info('💳 Creating Payment records...');

        $payments = [
            // demo_site_001 payments
            [
                'site_id' => 'demo_site_001',
                'amount' => 100000.00,
                'payment_method' => 'card',
                'status' => 'completed',
                'transaction_id' => 'txn_001_' . uniqid(),
                'payment_gateway' => 'stripe',
                'payment_data' => [
                    'card_last4' => '4242',
                    'card_brand' => 'visa'
                ],
                'completed_at' => now()->subDays(5),
            ],
            [
                'site_id' => 'demo_site_001',
                'amount' => 50000.00,
                'payment_method' => 'admin',
                'status' => 'completed',
                'transaction_id' => null,
                'payment_gateway' => null,
                'payment_data' => [],
                'notes' => 'Admin credit bonus',
                'completed_at' => now()->subDays(2),
            ],

            // demo_site_002 payments
            [
                'site_id' => 'demo_site_002',
                'amount' => 75000.00,
                'payment_method' => 'bank',
                'status' => 'completed',
                'transaction_id' => 'bank_002_' . uniqid(),
                'payment_gateway' => 'toss',
                'payment_data' => [
                    'bank_name' => '신한은행',
                    'account_number' => '110-123-****56'
                ],
                'completed_at' => now()->subDays(7),
            ],

            // demo_site_003 payments
            [
                'site_id' => 'demo_site_003',
                'amount' => 30000.00,
                'payment_method' => 'virtual',
                'status' => 'pending',
                'transaction_id' => 'virt_003_' . uniqid(),
                'payment_gateway' => 'kakao_pay',
                'payment_data' => [
                    'virtual_account' => '3333-01-123456'
                ],
                'completed_at' => null,
            ],
        ];

        foreach ($payments as $paymentData) {
            if (!SiteCreditPayment::where('transaction_id', $paymentData['transaction_id'])->exists()) {
                SiteCreditPayment::create($paymentData);
            }
        }

        $this->command->info('✓ Created ' . count($payments) . ' payment records');
    }

    private function createCreditMessages(): void
    {
        $this->command->info('📱 Creating Credit Messages...');

        $messages = [
            [
                'site_id' => 'demo_site_001',
                'title' => [
                    'ko' => '마케팅 캠페인 - 신규 고객 할인',
                    'en' => 'Marketing Campaign - New Customer Discount'
                ],
                'message_type' => 'alimtalk',
                'routing_strategy' => 'alimtalk_first',
                'message_content' => '🎉 신규 가입을 축하합니다! 첫 구매 시 20% 할인 혜택을 드립니다. 쿠폰코드: NEW20',
                'recipients' => [
                    '01012345678',
                    '01087654321',
                    '01055555555',
                    '01099999999'
                ],
                'status' => 'completed',
                'scheduled_at' => null,
                'sent_at' => now()->subDays(3),
                'estimated_cost' => 60.00,
                'actual_cost' => 60.00,
                'total_recipients' => 4,
                'success_count' => 4,
                'failed_count' => 0,
            ],
            [
                'site_id' => 'demo_site_001',
                'title' => [
                    'ko' => '예약 알림 - 내일 배송 예정',
                    'en' => 'Delivery Notification - Scheduled for Tomorrow'
                ],
                'message_type' => 'sms',
                'routing_strategy' => 'sms_only',
                'message_content' => '[배송알림] 주문하신 상품이 내일(12/1) 오전 중 배송 예정입니다. 문의: 1588-1234',
                'recipients' => [
                    '01012345678',
                    '01087654321'
                ],
                'status' => 'scheduled',
                'scheduled_at' => now()->addHours(2),
                'sent_at' => null,
                'estimated_cost' => 40.00,
                'actual_cost' => null,
                'total_recipients' => 2,
                'success_count' => 0,
                'failed_count' => 0,
            ],
            [
                'site_id' => 'demo_site_002',
                'title' => [
                    'ko' => '시스템 점검 안내',
                    'en' => 'System Maintenance Notice'
                ],
                'message_type' => 'lms',
                'routing_strategy' => 'cost_optimized',
                'message_content' => '안녕하세요. 서비스 품질 향상을 위한 시스템 점검이 2024년 12월 1일 오전 2시부터 6시까지 진행됩니다. 점검 시간 동안 일시적으로 서비스 이용이 제한될 수 있습니다. 불편을 드려 죄송합니다.',
                'recipients' => [
                    '01011111111',
                    '01022222222',
                    '01033333333'
                ],
                'status' => 'sending',
                'scheduled_at' => null,
                'sent_at' => now()->subMinutes(30),
                'estimated_cost' => 135.00,
                'actual_cost' => 135.00,
                'total_recipients' => 3,
                'success_count' => 2,
                'failed_count' => 1,
            ],
            [
                'site_id' => 'demo_site_003',
                'title' => [
                    'ko' => '이벤트 종료 안내',
                    'en' => 'Event End Notice'
                ],
                'message_type' => 'alimtalk',
                'routing_strategy' => 'alimtalk_first',
                'message_content' => '📢 할인 이벤트가 곧 종료됩니다! 마지막 기회를 놓치지 마세요.',
                'recipients' => [
                    '01044444444'
                ],
                'status' => 'failed',
                'scheduled_at' => null,
                'sent_at' => now()->subHours(1),
                'estimated_cost' => 20.00,
                'actual_cost' => 0.00,
                'total_recipients' => 1,
                'success_count' => 0,
                'failed_count' => 1,
            ],
        ];

        foreach ($messages as $messageData) {
            CreditMessage::create($messageData);
        }

        $this->command->info('✓ Created ' . count($messages) . ' credit messages');
    }

    private function createUsageRecords(): void
    {
        $this->command->info('📈 Creating Usage records...');

        $usages = [
            [
                'site_id' => 'demo_site_001',
                'message_type' => 'alimtalk',
                'quantity' => 4,
                'cost_per_unit' => 15.00,
                'total_cost' => 60.00,
                'refund_amount' => 0.00,
                'batch_id' => 'batch_' . uniqid(),
                'metadata' => [
                    'credit_message_id' => 1,
                    'campaign_name' => 'new_customer_discount'
                ],
                'status' => 'used',
            ],
            [
                'site_id' => 'demo_site_002',
                'message_type' => 'lms',
                'quantity' => 3,
                'cost_per_unit' => 45.00,
                'total_cost' => 135.00,
                'refund_amount' => 45.00, // 1개 실패로 인한 환불
                'refund_reason' => '전송 실패분 자동 환불',
                'refunded_at' => now()->subMinutes(25),
                'batch_id' => 'batch_' . uniqid(),
                'metadata' => [
                    'credit_message_id' => 3,
                    'campaign_name' => 'system_maintenance'
                ],
                'status' => 'refunded',
            ],
            [
                'site_id' => 'demo_site_003',
                'message_type' => 'alimtalk',
                'quantity' => 1,
                'cost_per_unit' => 20.00,
                'total_cost' => 20.00,
                'refund_amount' => 20.00,
                'refund_reason' => 'API 전송 실패',
                'refunded_at' => now()->subMinutes(50),
                'batch_id' => 'batch_' . uniqid(),
                'metadata' => [
                    'credit_message_id' => 4,
                    'campaign_name' => 'event_end_notice'
                ],
                'status' => 'refunded',
            ],
            [
                'site_id' => 'demo_site_001',
                'message_type' => 'sms',
                'quantity' => 2,
                'cost_per_unit' => 20.00,
                'total_cost' => 40.00,
                'refund_amount' => 0.00,
                'batch_id' => 'batch_' . uniqid(),
                'metadata' => [
                    'credit_message_id' => 2,
                    'campaign_name' => 'delivery_notification'
                ],
                'status' => 'reserved', // 예약된 메시지
            ],
        ];

        foreach ($usages as $usageData) {
            SiteCreditUsage::create($usageData);
        }

        $this->command->info('✓ Created ' . count($usages) . ' usage records');
    }

    private function createSendLogs(): void
    {
        $this->command->info('📋 Creating Send Logs...');

        // Usage ID들을 가져옴
        $usages = SiteCreditUsage::all();

        $sendLogs = [
            [
                'usage_id' => $usages[0]->id, // demo_site_001 alimtalk
                'message_ids' => ['msg_001_001', 'msg_001_002', 'msg_001_003', 'msg_001_004'],
                'message_type' => 'alimtalk',
                'total_count' => 4,
                'success_count' => 4,
                'failed_count' => 0,
                'webhook_result' => [
                    'delivery_results' => [
                        ['message_id' => 'msg_001_001', 'status' => 'delivered'],
                        ['message_id' => 'msg_001_002', 'status' => 'delivered'],
                        ['message_id' => 'msg_001_003', 'status' => 'delivered'],
                        ['message_id' => 'msg_001_004', 'status' => 'delivered'],
                    ],
                    'processed_at' => now()->subDays(3)->toISOString()
                ],
                'webhook_received_at' => now()->subDays(3),
                'settlement_status' => 'completed',
                'final_cost' => 60.00,
                'settled_at' => now()->subDays(3),
                'error_message' => null,
                'retry_count' => 0,
            ],
            [
                'usage_id' => $usages[1]->id, // demo_site_002 lms
                'message_ids' => ['msg_002_001', 'msg_002_002', 'msg_002_003'],
                'message_type' => 'lms',
                'total_count' => 3,
                'success_count' => 2,
                'failed_count' => 1,
                'webhook_result' => [
                    'delivery_results' => [
                        ['message_id' => 'msg_002_001', 'status' => 'delivered'],
                        ['message_id' => 'msg_002_002', 'status' => 'delivered'],
                        ['message_id' => 'msg_002_003', 'status' => 'failed', 'error' => 'Invalid phone number'],
                    ],
                    'processed_at' => now()->subMinutes(25)->toISOString()
                ],
                'webhook_received_at' => now()->subMinutes(25),
                'settlement_status' => 'completed',
                'final_cost' => 90.00,
                'settled_at' => now()->subMinutes(20),
                'error_message' => null,
                'retry_count' => 0,
            ],
            [
                'usage_id' => $usages[2]->id, // demo_site_003 alimtalk failed
                'message_ids' => ['msg_003_001'],
                'message_type' => 'alimtalk',
                'total_count' => 1,
                'success_count' => 0,
                'failed_count' => 1,
                'webhook_result' => [
                    'delivery_results' => [
                        ['message_id' => 'msg_003_001', 'status' => 'failed', 'error' => 'Template not found'],
                    ],
                    'processed_at' => now()->subMinutes(50)->toISOString()
                ],
                'webhook_received_at' => now()->subMinutes(50),
                'settlement_status' => 'completed',
                'final_cost' => 0.00,
                'settled_at' => now()->subMinutes(45),
                'error_message' => 'Template not found',
                'retry_count' => 1,
            ],
        ];

        foreach ($sendLogs as $logData) {
            MessageSendLog::create($logData);
        }

        $this->command->info('✓ Created ' . count($sendLogs) . ' send logs');
    }
}
