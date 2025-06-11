# 📦 Techigh Credit Messaging

> 크레딧 기반 알림톡/SMS 발송 시스템 - Laravel 멀티테넌트 지원 패키지

[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-%5E10.0%7C%5E11.0-red.svg)](https://laravel.com/)
[![Orchid Platform](https://img.shields.io/badge/orchid-%5E14.0-purple.svg)](https://orchid.software/)

## 🚀 소개

**Techigh Credit Messaging**은 Laravel 기반의 크레딧 기반 메시징 시스템으로, 멀티테넌트 환경에서 알림톡과 SMS 발송을 통합 관리할 수 있는 포괄적인 솔루션입니다.

### ✨ 주요 특징

- 🏢 **멀티테넌트 지원**: Universal, Tenant, Central 모드
- 💳 **크레딧 시스템**: 메시지 유형별 차등 단가 및 자동 정산
- 🔄 **스마트 라우팅**: 알림톡 실패 시 SMS 자동 대체 발송
- 🎯 **Orchid 통합**: 관리자 패널을 통한 직관적인 관리
- 📊 **실시간 통계**: 발송 현황 및 크레딧 사용량 모니터링
- 🔗 **외부 연동**: SendGo 메시지 플랫폼 및 Toss Payments 결제

---

## 📋 요구사항

- **PHP**: ^8.1
- **Laravel**: ^10.0 | ^11.0
- **Orchid Platform**: ^14.0
- **Dependencies**: GuzzleHTTP, Ramsey UUID

---

## 🔧 설치

### 1. Composer 설정

`composer.json` 파일의 `repositories` 섹션에 다음을 추가하세요:

```json
{
    "repositories": {
        "techigh/credit-messaging": {
            "type": "vcs",
            "url": "https://github.com/taekwanpark/credit-messaging.git"
        }
    }
}
```

### 2. 패키지 설치

`composer.json` 파일의 `require` 섹션에 다음을 추가하세요:

```json
{
    "require": {
        "techigh/credit-messaging": "^1.1.4"
    }
}
```

그리고 Composer 설치를 실행하세요:

```bash
composer install
# 또는
composer update techigh/credit-messaging
```

### 3. 설정 파일 발행

> **참고**: Laravel의 Package Auto-Discovery 기능으로 서비스 프로바이더와 Facade가 자동으로 등록됩니다.

```bash
php artisan vendor:publish --provider="Techigh\CreditMessaging\Providers\CreditMessagingServiceProvider" --tag="config"
```

### 4. 마이그레이션 파일 발행

```bash
php artisan vendor:publish --provider="Techigh\CreditMessaging\Providers\CreditMessagingServiceProvider" --tag="migrations"
```

### 5. 마이그레이션 실행

```bash
php artisan migrate

php artisan tenants:migrate
```

### 6. 웹훅 시크릿 생성

```bash
php artisan credit-messaging:generate-webhook-secret
```

---

## ⚙️ 환경 설정

`.env` 파일에 다음 설정을 추가하세요:

```dotenv
# =============================================================================
# Credit Messaging 기본 설정
# =============================================================================

# 라우팅 모드 (tenant|central|universal)
CREDIT_MESSAGING_ROUTE_MODE=tenant

# =============================================================================
# SendGo 메시지 플랫폼 연동
# =============================================================================

MESSAGE_PLATFORM_ACCESS_KEY=your_access_key_here
MESSAGE_PLATFORM_SECRET_KEY=your_secret_key_here
MESSAGE_PLATFORM_SMS_SENDER_KEY=your_sms_sender_key_here
MESSAGE_PLATFORM_KAKAO_SENDER_KEY=your_kakao_sender_key_here

# =============================================================================
# 웹훅 설정
# =============================================================================

# 웹훅 시크릿 (필수 - 위 명령어로 생성)
CREDIT_MESSAGING_WEBHOOK_SECRET=generated_secret_key_here

# 웹훅 검증 설정
WEBHOOK_VERIFY_SIGNATURE=true
WEBHOOK_LOG_ERRORS=true
WEBHOOK_TIMEOUT=30
WEBHOOK_RETRY_ATTEMPTS=3

# =============================================================================
# 기본 크레딧 단가 설정 (KRW)
# =============================================================================

DEFAULT_ALIMTALK_COST=8.00
DEFAULT_SMS_COST=15.00
DEFAULT_LMS_COST=45.00
DEFAULT_MMS_COST=120.00

# =============================================================================
# 메시지 제한 설정
# =============================================================================

MAX_RECIPIENTS_PER_BATCH=1000
```

---

## 🎯 사용법

### Facade를 통한 메시지 발송

```php
<?php

use MessageSend;

// 알림톡 발송 (대체 SMS 포함)
$result = MessageSend::sendAlimtalk([
    'type' => 'alimtalk',
    'templateCode' => 'TEMPLATE_001',
    'replaceSms' => true,
    'smsTitle' => '중요 알림',
    'smsContent' => '알림톡 전송에 실패하여 SMS로 발송됩니다.',
    'sendAt' => '2024-12-25 10:00:00', // 예약 발송 (선택)
    'contacts' => [
        [
            'phone_e164' => '+821012345678',
            'name' => '홍길동'
        ],
        [
            'phone_e164' => '+821087654321', 
            'name' => '김철수'
        ]
    ]
]);

// 발송 결과 확인
if ($result['success']) {
    echo "캠페인 ID: " . $result['campaign_id'];
    echo "발송 요청 완료: " . $result['total_count'] . "건";
} else {
    echo "발송 실패: " . $result['message'];
}
```

### Service 클래스 직접 사용

```php
<?php

use Techigh\CreditMessaging\Services\MessageSendService;

class YourController extends Controller
{
    public function __construct(
        private MessageSendService $messageSend
    ) {}

    public function sendMessage()
    {
        $result = $this->messageSend->sendAlimtalk([
            'type' => 'alimtalk',
            'templateCode' => 'WELCOME_001',
            'replaceSms' => true,
            'smsContent' => '회원가입을 환영합니다!',
            'contacts' => [
                ['phone_e164' => '+821012345678', 'name' => '신규회원']
            ]
        ]);

        return response()->json($result);
    }
}
```

### 크레딧 관리

```php
<?php

use CreditHandler;

// 크레딧 잔액 확인
$balance = CreditHandler::getBalance();

// 크레딧 구매 (관리자 패널에서도 가능)
$purchase = CreditHandler::purchase([
    'amount' => 100000, // 100,000원
    'payment_method' => 'card'
]);

// 크레딧 사용 내역 조회
$usages = CreditHandler::getUsageHistory([
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31'
]);
```

---

## 📊 데이터 모델

### 주요 테이블 구조

#### site_campaigns
```sql
-- 메시지 캠페인 정보
id, type, status, total_count, success_count, failed_count,
template_code, replace_sms, send_at, created_at, updated_at
```

#### site_credits  
```sql
-- 크레딧 구매/충전 내역
id, type, amount, balance_credits, cost_per_credit,
payment_id, status, created_at, updated_at
```

#### site_campaign_messages
```sql
-- 개별 메시지 발송 내역  
id, campaign_id, recipient_name, phone_e164,
kakao_status, sms_status, created_at, updated_at
```

#### site_credit_usages
```sql
-- 크레딧 사용/환급 내역
id, campaign_id, usage_type, credit_count, unit_cost,
total_cost, created_at, updated_at
```

---

## 🔄 발송 프로세스

### 1. 발송 전 검증
- 크레딧 잔액 확인
- 수신자 전화번호 E164 포맷 검증
- 메시지 길이 및 개수 제한 확인

### 2. 캠페인 생성 및 크레딧 차감
- 캠페인 레코드 생성
- 개별 메시지 레코드 생성
- 예상 사용 크레딧 사전 차감

### 3. 외부 API 호출
- SendGo 메시지 플랫폼으로 발송 요청
- 웹훅 URL 및 시크릿 설정
- API 응답 검증 및 저장

### 4. 결과 처리 (웹훅)
- 발송 결과 수신 및 검증
- 캠페인 통계 업데이트
- 실패분 크레딧 자동 환급

---

## 🛠️ 관리자 패널 (Orchid)

패키지 설치 후 Orchid 관리자 패널에서 다음 메뉴를 사용할 수 있습니다:

### 💡 크레딧 등급
- **경로**: `/settings/site-plans`
- **기능**: 등급 목록, 상세 조회, 테넌트 연결

### 📱 캠페인 관리
- **경로**: `/settings/site-campaigns`
- **기능**: 발송 캠페인 목록, 상세 조회, 통계 확인

### 💰 크레딧 관리  
- **경로**: `/settings/site-credits`
- **기능**: 크레딧 구매, 충전, 잔액 조회

### 📊 사용 내역
- **경로**: `/settings/site-credit-usages` 
- **기능**: 크레딧 사용/환급 내역 조회

### 💳 결제 관리
- **경로**: `/settings/payments`
- **기능**: Toss Payments 결제 내역 관리

---

## 🔗 웹훅 API

### 발송 결과 수신 엔드포인트

```
POST /api/webhooks/credit-messaging/delivery-status
```

**헤더**:
```
Content-Type: application/json
X-Webhook-Signature: sha256=generated_signature
```

**페이로드 예시**:
```json
{
    "campaign_id": "12345",
    "results": [
        {
            "message_id": "msg_001",  
            "phone": "+821012345678",
            "kakao_status": "success",
            "sms_status": null
        },
        {
            "message_id": "msg_002",
            "phone": "+821087654321", 
            "kakao_status": "failed",
            "sms_status": "success"
        }
    ]
}
```

---

## 🎨 커스터마이징

### 설정 파일 수정

`config/credit-messaging.php`에서 다음 항목들을 커스터마이징할 수 있습니다:

```php
<?php

return [
    // 메시지 플랫폼 설정
    'message_platform' => [
        'url' => env('MESSAGE_PLATFORM_URL', 'https://your-platform.com'),
        // ...
    ],
    
    // 기본 단가 설정
    'default_credit_costs' => [
        'alimtalk' => 8.00,
        'sms' => 15.00,
        'lms' => 45.00,
        'mms' => 120.00,
    ],
    
    // 발송 제한 설정
    'limits' => [
        'max_recipients_per_batch' => 1000,
        'max_message_length' => [
            'sms' => 90,
            'lms' => 2000,
            'alimtalk' => 1000,
        ],
    ],
];
```

### 이벤트 리스너 추가

```php
<?php

// app/Listeners/CampaignCompletedListener.php
use Techigh\CreditMessaging\Events\CampaignCompleted;

class CampaignCompletedListener
{
    public function handle(CampaignCompleted $event)
    {
        // 캠페인 완료 후 커스텀 로직 실행
        $campaign = $event->campaign;
        
        // 예: 관리자에게 알림 발송
        // 예: 통계 데이터 업데이트
    }
}
```

---

## 🚨 트러블슈팅

### 자주 발생하는 문제들

#### 1. 웹훅 서명 검증 실패
```bash
# 웹훅 시크릿 재생성
php artisan credit-messaging:generate-webhook-secret

# 환경변수 확인
echo $CREDIT_MESSAGING_WEBHOOK_SECRET
```

#### 2. 크레딧 차감 오류
```php
// 크레딧 잔액 확인
use CreditHandler;
$balance = CreditHandler::getBalance();

// 디버그 로그 확인
tail -f storage/logs/laravel.log | grep credit
```

#### 3. 전화번호 포맷 오류
```php
// E164 포맷 확인 및 변환
use Techigh\CreditMessaging\Services\PhoneNumberFormatter;

$formatted = PhoneNumberFormatter::toE164('010-1234-5678', 'KR');
// 결과: +821012345678
```

### 로그 확인

```bash
# 크레딧 관련 로그
tail -f storage/logs/credit.log

# 전체 애플리케이션 로그
tail -f storage/logs/laravel.log | grep CreditMessaging
```

---

## 🧪 테스트

```bash
# 전체 테스트 실행
composer test

# 커버리지 리포트 생성
composer test-coverage
```

---

## 📈 성능 최적화

### 대용량 발송 시 권장사항

1. **배치 처리**: 1,000건 단위로 분할 발송
2. **큐 사용**: 발송 작업을 큐로 처리
3. **인덱스 확인**: 전화번호 및 캠페인 ID 인덱스

```php
// 대용량 발송 예시
use Illuminate\Support\Collection;

$contacts = collect($largeContactList);

$contacts->chunk(1000)->each(function ($chunk) {
    MessageSend::sendAlimtalk([
        'type' => 'alimtalk',
        'templateCode' => 'BULK_001',
        'contacts' => $chunk->toArray()
    ]);
    
    // 처리 간격 조절
    sleep(1);
});
```


---

## 📄 라이선스

이 프로젝트는 [MIT 라이선스](LICENSE.md) 하에 배포됩니다.

---

## 📧 지원

- **Email**: techigh@amuz.co.kr
- **Issues**: [GitHub Issues](https://github.com/techigh/credit-messaging/issues)
- **Documentation**: [패키지 문서](https://docs.techigh.com/credit-messaging)

---

## 🔄 업데이트 로그

### v1.0.0 (2024-12-09)
- 초기 릴리즈
- 멀티테넌트 지원
- SendGo 플랫폼 연동
- Toss Payments 결제 연동
- Orchid 관리자 패널 통합