<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 크레딧 메시징 시스템 설정
    |--------------------------------------------------------------------------
    |
    | 크레딧 기반 메시징 시스템의 전체 설정을 관리합니다.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | 메시지 서비스 API 설정
    |--------------------------------------------------------------------------
    |
    | 외부 메시지 서비스와 연동하기 위한 API 설정
    |
    */
    'message_service' => [
        'base_url' => env('MESSAGE_SERVICE_BASE_URL', 'https://api.sendgo.io'),
        'access_key' => env('MESSAGE_SERVICE_ACCESS_KEY', ''),
        'secret_key' => env('MESSAGE_SERVICE_SECRET_KEY', ''),

        'api_key' => env('MESSAGE_SERVICE_API_KEY', ''),

        'timeout' => env('MESSAGE_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('MESSAGE_SERVICE_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('MESSAGE_SERVICE_RETRY_DELAY', 5), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | 웹훅 설정
    |--------------------------------------------------------------------------
    |
    | 메시지 전송 결과를 받기 위한 웹훅 설정
    |
    */
    'webhook' => [
        'secret' => env('MESSAGE_SERVICE_WEBHOOK_SECRET', ''),
        'signature_header' => env('WEBHOOK_SIGNATURE_HEADER', 'X-Webhook-Signature'),
        'timeout' => env('WEBHOOK_TIMEOUT', 30),
        'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | 기본 메시지 단가 설정
    |--------------------------------------------------------------------------
    |
    | 새로운 사이트 생성시 적용되는 기본 단가 (KRW 원 단위)
    |
    */
    'default_costs' => [
        'alimtalk' => env('DEFAULT_ALIMTALK_COST', 15.00),
        'sms' => env('DEFAULT_SMS_COST', 20.00),
        'lms' => env('DEFAULT_LMS_COST', 50.00),
        'mms' => env('DEFAULT_MMS_COST', 200.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | 자동 충전 설정
    |--------------------------------------------------------------------------
    |
    | 크레딧 잔액이 부족할 때 자동으로 충전하는 설정
    |
    */
    'auto_charge' => [
        'enabled' => env('AUTO_CHARGE_ENABLED', false),
        'default_threshold' => env('AUTO_CHARGE_THRESHOLD', 10000.00),
        'default_amount' => env('AUTO_CHARGE_AMOUNT', 50000.00),
        'payment_method' => env('AUTO_CHARGE_PAYMENT_METHOD', 'admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 메시지 라우팅 설정
    |--------------------------------------------------------------------------
    |
    | 메시지 전송 방식과 폴백 전략 설정
    |
    */
    'routing' => [
        'default_strategy' => env('DEFAULT_ROUTING_STRATEGY', 'alimtalk_first'),
        'strategies' => [
            'alimtalk_first' => '알림톡 우선 (SMS 폴백)',
            'sms_only' => 'SMS 전용',
            'cost_optimized' => '비용 최적화',
        ],
        'fallback_enabled' => env('FALLBACK_ENABLED', true),
        'cost_optimization' => env('COST_OPTIMIZATION_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 메시지 제한 설정
    |--------------------------------------------------------------------------
    |
    | 메시지 전송 횟수 및 크기 제한
    |
    */
    'limits' => [
        'max_recipients_per_batch' => env('MAX_RECIPIENTS_PER_BATCH', 1000),
        'max_message_length' => [
            'sms' => 90,
            'lms' => 2000,
            'mms' => 2000,
            'alimtalk' => 1000,
        ],
        'daily_send_limit' => env('DAILY_SEND_LIMIT', 10000),
        'hourly_send_limit' => env('HOURLY_SEND_LIMIT', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | 로깅 설정
    |--------------------------------------------------------------------------
    |
    | 크레딧 메시징 시스템 관련 로깅 설정
    |
    */
    'logging' => [
        'enabled' => env('CREDIT_MESSAGING_LOG_ENABLED', true),
        'channel' => env('CREDIT_MESSAGING_LOG_CHANNEL', 'stack'),
        'level' => env('CREDIT_MESSAGING_LOG_LEVEL', 'info'),
        'log_webhook_payloads' => env('LOG_WEBHOOK_PAYLOADS', false),
        'log_api_requests' => env('LOG_API_REQUESTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 캐시 설정
    |--------------------------------------------------------------------------
    |
    | 성능 향상을 위한 캐시 설정
    |
    */
    'cache' => [
        'enabled' => env('CREDIT_MESSAGING_CACHE_ENABLED', true),
        'ttl' => [
            'site_credit' => env('SITE_CREDIT_CACHE_TTL', 300), // 5 minutes
            'usage_stats' => env('USAGE_STATS_CACHE_TTL', 3600), // 1 hour
            'message_status' => env('MESSAGE_STATUS_CACHE_TTL', 900), // 15 minutes
        ],
        'prefix' => env('CREDIT_MESSAGING_CACHE_PREFIX', 'credit_msg'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 알림 설정
    |--------------------------------------------------------------------------
    |
    | 시스템 이벤트에 대한 알림 설정
    |
    */
    'notifications' => [
        'enabled' => env('CREDIT_MESSAGING_NOTIFICATIONS_ENABLED', true),
        'low_balance_threshold' => env('LOW_BALANCE_NOTIFICATION_THRESHOLD', 5000.00),
        'failed_payment_notification' => env('FAILED_PAYMENT_NOTIFICATION_ENABLED', true),
        'webhook_failure_notification' => env('WEBHOOK_FAILURE_NOTIFICATION_ENABLED', true),
        'daily_usage_report' => env('DAILY_USAGE_REPORT_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | 보안 설정
    |--------------------------------------------------------------------------
    |
    | 크레딧 메시징 시스템의 보안 관련 설정
    |
    */
    'security' => [
        'ip_whitelist' => env('MESSAGE_SERVICE_IP_WHITELIST', ''),
        'require_authentication' => env('REQUIRE_MESSAGE_AUTHENTICATION', true),
        'api_rate_limit' => env('MESSAGE_API_RATE_LIMIT', 100), // per minute
        'webhook_verification' => env('WEBHOOK_VERIFICATION_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 큐 설정
    |--------------------------------------------------------------------------
    |
    | 비동기 작업을 위한 큐 설정
    |
    */
    'queue' => [
        'connection' => env('CREDIT_MESSAGING_QUEUE_CONNECTION', 'redis'),
        'webhook_queue' => env('WEBHOOK_QUEUE_NAME', 'webhook-processing'),
        'message_queue' => env('MESSAGE_QUEUE_NAME', 'message-sending'),
        'settlement_queue' => env('SETTLEMENT_QUEUE_NAME', 'settlement-processing'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 개발 및 테스트 설정
    |--------------------------------------------------------------------------
    |
    | 개발 환경에서 사용하는 설정
    |
    */
    'development' => [
        'mock_api_enabled' => env('MOCK_MESSAGE_API_ENABLED', false),
        'test_phone_numbers' => env('TEST_PHONE_NUMBERS', '01012345678,01087654321'),
        'sandbox_mode' => env('MESSAGE_SANDBOX_MODE', false),
        'fake_webhook_delay' => env('FAKE_WEBHOOK_DELAY', 5), // seconds
    ],
];