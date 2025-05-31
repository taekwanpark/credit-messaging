# Techigh Credit Messaging

**Languages:** [English](README.md) | [한국어](README-ko.md)

**Documentation:** [Architecture](ARCHITECTURE.md)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Techigh/credit-messaging.svg?style=flat-square)](https://packagist.org/packages/Techigh/credit-messaging)
[![Total Downloads](https://img.shields.io/packagist/dt/Techigh/credit-messaging.svg?style=flat-square)](https://packagist.org/packages/Techigh/credit-messaging)
[![License](https://img.shields.io/packagist/l/Techigh/credit-messaging.svg?style=flat-square)](https://packagist.org/packages/Techigh/credit-messaging)

A comprehensive credit-based messaging system for Laravel with multi-tenant support, smart routing, and automatic settlement.

## Features

🏦 **Credit Management**
- Site-specific credit balances
- Flexible pricing per message type
- Auto-charge with configurable thresholds
- Payment tracking with multiple gateways
- Automatic refunds for failed messages

📱 **Message Routing**
- **Alimtalk First**: Attempt Alimtalk, fallback to SMS
- **SMS Only**: Direct SMS sending
- **Cost Optimized**: Choose cheapest available option
- **Scheduled messaging** with queue processing

💰 **Settlement & Billing**
- Real-time cost tracking
- Automatic settlement based on delivery results
- Detailed usage analytics
- Refund processing for failures

🔧 **Admin Integration**
- Laravel Orchid platform support
- Comprehensive CRUD interfaces
- Real-time monitoring dashboards
- Detailed reporting capabilities

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or 11.0
- Laravel Orchid 14.0 or higher

## Installation

Install the package via Composer:

```bash
composer require Techigh/credit-messaging
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="credit-messaging-migrations"
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="credit-messaging-config"
```

## Configuration

Add the following environment variables to your `.env` file:

```env
# Message Service API Configuration
ALIMTALK_API_URL="https://api.alimtalk-service.com"
ALIMTALK_API_KEY="your-alimtalk-api-key"

SMS_API_URL="https://api.sms-service.com"
SMS_API_KEY="your-sms-api-key"

LMS_API_URL="https://api.lms-service.com"
LMS_API_KEY="your-lms-api-key"

MMS_API_URL="https://api.mms-service.com"
MMS_API_KEY="your-mms-api-key"

# Webhook Configuration
CREDIT_MESSAGING_WEBHOOK_SECRET="your-webhook-secret"
```

## Quick Start

### 1. Seed Demo Data

```bash
php artisan credit-messaging:seed
```

### 2. Basic Usage

```php
use Techigh\CreditMessaging\Facades\CreditManager;
use Techigh\CreditMessaging\Facades\MessageRouter;
use Techigh\CreditMessaging\Models\CreditMessage;

// Get site credit information
$siteCredit = CreditManager::getSiteCredit('site_001');

// Check current balance
$balance = CreditManager::getBalance('site_001');

// Create and send a message
$creditMessage = CreditMessage::create([
    'site_id' => 'site_001',
    'title' => ['ko' => '마케팅 메시지', 'en' => 'Marketing Message'],
    'message_type' => 'alimtalk',
    'routing_strategy' => 'alimtalk_first',
    'message_content' => '안녕하세요! 특별 할인 이벤트를 확인해보세요.',
    'recipients' => ['01012345678', '01087654321'],
    'status' => 'draft'
]);

$results = MessageRouter::sendMessage($creditMessage);
```

## API Reference

### CreditManager Facade

```php
// Get site credit configuration
$siteCredit = CreditManager::getSiteCredit(string $siteId);

// Get current balance
$balance = CreditManager::getBalance(string $siteId);

// Charge credits
$usage = CreditManager::chargeCredits(string $siteId, float $amount, array $metadata = []);

// Refund credits
$refund = CreditManager::refundCredits(SiteCreditUsage $usage, float $amount, string $reason);

// Add payment
$payment = CreditManager::addPayment(string $siteId, float $amount, string $method, array $data = []);

// Complete payment
$success = CreditManager::completePayment(SiteCreditPayment $payment);

// Get usage statistics
$stats = CreditManager::getUsageStats(string $siteId, $startDate, $endDate);
```

### MessageRouter Facade

```php
// Send message immediately
$result = MessageRouter::sendMessage(CreditMessage $creditMessage);

// Schedule message for later
$success = MessageRouter::scheduleMessage(CreditMessage $creditMessage);

// Estimate message cost
$estimation = MessageRouter::estimateMessageCost(string $siteId, string $messageType, int $recipientCount, string $content = null);

// Get batch status
$status = MessageRouter::getBatchStatus(string $batchId);

// Process webhook (called automatically)
$result = MessageRouter::processWebhook(string $provider, array $payload);
```

## Database Schema

The package creates the following tables:

- `site_credits` - Credit balances and pricing configuration
- `site_credit_payments` - Payment and charging records
- `site_credit_usages` - Credit consumption tracking
- `credit_messages` - Message campaigns and templates
- `message_send_logs` - Delivery logs and settlement data

## Orchid Admin Integration

If you're using Laravel Orchid, the package provides admin screens at:

- `/admin/credit-messages` - Manage message campaigns
- `/admin/site-credits` - Manage credit balances
- `/admin/site-credit-payments` - Track payments
- `/admin/site-credit-usages` - Monitor usage
- `/admin/message-send-logs` - View delivery logs

## Webhook Processing

The package automatically handles webhooks from message service providers:

```
POST /api/credit-messaging/webhook/{provider}
```

Supported providers: `alimtalk`, `sms`, `lms`, `mms`

## Queue Configuration

For optimal performance, configure your queues to process scheduled messages:

```bash
php artisan queue:work --queue=credit-messaging
```

## Testing

Run the package tests:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Techigh](https://github.com/Techigh)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.