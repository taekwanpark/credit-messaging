# Changelog

All notable changes to `Techigh/credit-messaging` will be documented in this file.

## 1.0.0 - 2024-12-01

### Added
- Initial release of Techigh Credit Messaging package
- Multi-tenant credit management system
- Support for Alimtalk, SMS, LMS, and MMS messaging
- Smart message routing with fallback strategies
- Automatic settlement based on delivery results
- Webhook processing for real-time delivery updates
- Laravel Orchid admin integration
- Comprehensive seeding system with demo data
- Queue-based scheduled messaging
- Payment tracking and auto-charging capabilities
- Detailed usage analytics and reporting
- REST API endpoints for webhook processing
- Comprehensive test coverage
- Multi-language support for message templates

### Features
- **Credit Management**: Site-specific balances, pricing, auto-charge
- **Message Routing**: Alimtalk-first, SMS-only, cost-optimized strategies
- **Settlement**: Real-time cost tracking, automatic refunds
- **Admin Interface**: Full Orchid integration with CRUD screens
- **Webhooks**: Secure webhook processing with signature validation
- **Scheduling**: Queue-based message scheduling with retry logic
- **Analytics**: Detailed usage statistics and reporting

### Database Schema
- `site_credits` - Credit configuration and balances
- `site_credit_payments` - Payment tracking
- `site_credit_usages` - Credit consumption logs
- `credit_messages` - Message campaigns
- `message_send_logs` - Delivery and settlement logs

### API Coverage
- CreditManager facade for credit operations
- MessageRouter facade for message routing
- Webhook endpoints for external integrations
- Command-line tools for data seeding

### Supported Platforms
- Laravel 10.x and 11.x
- PHP 8.1+
- Laravel Orchid 14.x
- MySQL/PostgreSQL databases
- Redis for queue processing