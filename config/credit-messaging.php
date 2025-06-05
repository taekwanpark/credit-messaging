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
    'message_platform' => [
        'url' => env('MESSAGE_PLATFORM_URL', 'https://sendgo.io'),
        'version' => env('MESSAGE_PLATFORM_VERSION', 'v1'),
        'access_key' => env('MESSAGE_PLATFORM_ACCESS_KEY', 'bca21cd56accf202c28c49653a5cb123'),
        'secret_key' => env('MESSAGE_PLATFORM_SECRET_KEY', 'af7a8b3e83b65601c866faa0a62e3669ce8780820c4bb0c2d341b8b03dad9c54'),
        'sms_sender_key' => env('MESSAGE_PLATFORM_SMS_SENDER_KEY', '9cd5460b-6458-4edc-9b11-c26d3013c340'),
        'kakao_sender_key' => env('MESSAGE_PLATFORM_KAKAO_SENDER_KEY', 'ea56971906a40a93ecf6c3fc796e9173a3a052ca'),
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
        'uri' => env('MESSAGE_WEBHOOK_URI', 'api/webhooks/credit-messaging/delivery-status'),
        'secret' => env('CREDIT_MESSAGING_WEBHOOK_SECRET', ''),
        'signature_header' => env('WEBHOOK_SIGNATURE_HEADER', 'X-Webhook-Signature'),
        'timeout' => env('WEBHOOK_TIMEOUT', 30),
        'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 300), // seconds
        
        'verify_signature' => env('WEBHOOK_VERIFY_SIGNATURE', true),
        'log_errors' => env('WEBHOOK_LOG_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 기본 메시지 단가 설정
    |--------------------------------------------------------------------------
    |
    | 새로운 사이트 생성시 적용되는 기본 단가 (KRW 원 단위)
    |
    */
    'default_credit_costs' => [
        'cost_per_credit' => env('DEFAULT_ALIMTALK_COST', 8.00),
        'alimtalk' => env('DEFAULT_ALIMTALK_COST', 1),
        'sms' => env('DEFAULT_SMS_COST', 1.5),
        'lms' => env('DEFAULT_LMS_COST', 4.5),
        'mms' => env('DEFAULT_MMS_COST', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | 자동 충전 설정
    |--------------------------------------------------------------------------
    |
    | 크레딧 잔액이 부족할 때 자동으로 충전하는 설정
    |
    */
//    'auto_charge' => [
//        'enabled' => env('AUTO_CHARGE_ENABLED', false),
//        'default_threshold' => env('AUTO_CHARGE_THRESHOLD', 10000.00),
//        'default_amount' => env('AUTO_CHARGE_AMOUNT', 50000.00),
//        'payment_method' => env('AUTO_CHARGE_PAYMENT_METHOD', 'admin'),
//    ],

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
//        'daily_send_limit' => env('DAILY_SEND_LIMIT', 10000),
//        'hourly_send_limit' => env('HOURLY_SEND_LIMIT', 1000),
    ],

];
