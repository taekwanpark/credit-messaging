# Techigh 크레딧 메시징 시스템

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Techigh/credit-messaging.svg?style=flat-square)](https://packagist.org/packages/Techigh/credit-messaging)
[![Total Downloads](https://img.shields.io/packagist/dt/Techigh/credit-messaging.svg?style=flat-square)](https://packagist.org/packages/Techigh/credit-messaging)
[![License](https://img.shields.io/packagist/l/Techigh/credit-messaging.svg?style=flat-square)](https://packagist.org/packages/Techigh/credit-messaging)

멀티테넌트 지원, 스마트 라우팅, 자동 정산 기능을 갖춘 Laravel용 종합 크레딧 기반 메시징 시스템입니다.

---

## 📄 설치 가이드 (Composer VCS 방식)

이 패키지는 아직 Packagist에 등록되어 있지 않습니다.
하지만 GitHub 저장소를 직접 참조하여 Composer를 통해 설치할 수 있습니다.

---

### 🔧 1단계: `composer.json`에 VCS 저장소 추가

Laravel 프로젝트의 `composer.json` 파일에 아래 내용을 추가하세요:

```json
"repositories": [
{
"type": "vcs",
"url": "https://github.com/taekwanpark/credit-messaging.git"
}
]
```

> `"require"` 항목 **위쪽**에 위치해야 합니다.

---

### 📦 2단계: Composer로 패키지 설치

#### ✅ 옵션 A: 태그된 안정 버전 설치 (권장)

```bash
composer require techigh/credit-messaging:^1.0
```

> `v1.0.0` 같은 Git 태그가 존재해야 합니다.

#### ✅ 옵션 B: 브랜치 기준 설치 (`main` 등)

```bash
composer require techigh/credit-messaging:dev-main
```

> 브랜치로 설치할 때는 반드시 `dev-` 접두사를 붙여야 합니다.

---

### ✅ Laravel 자동 등록 지원

Laravel 5.5 이상 (Laravel 10, 11 포함) 환경에서는
**서비스 프로바이더와 파사드 자동 등록** 기능이 동작하므로, 따로 설정할 필요가 없습니다.

---

### 📚 선택 사항: 설정 파일 / 마이그레이션 게시

패키지에서 설정 파일, 마이그레이션 등을 제공할 경우 다음 명령어로 게시할 수 있습니다:

```bash
php artisan vendor:publish --provider="Techigh\CreditMessaging\Providers\CreditMessagingServiceProvider"
```

---

성공적으로 설치되셨다면 즐거운 메시징 개발 되세요! 🚀
더 자세한 정보는 GitHub 저장소를 참고해주세요:
🔗 [https://github.com/taekwanpark/credit-messaging](https://github.com/taekwanpark/credit-messaging)

---

## 주요 기능

🏦 **크레딧 관리**

- 사이트별 크레딧 잔액 관리
- 메시지 유형별 유연한 가격 설정
- 설정 가능한 임계값으로 자동 충전
- 다중 결제 게이트웨이 결제 추적
- 실패한 메시지에 대한 자동 환불

📱 **메시지 라우팅**

- **알림톡 우선**: 알림톡 시도 후 SMS로 폴백
- **SMS 전용**: 직접 SMS 발송
- **비용 최적화**: 가장 저렴한 옵션 선택
- 큐 처리를 통한 **예약 메시징**

💰 **정산 및 빌링**

- 실시간 비용 추적
- 전송 결과 기반 자동 정산
- 상세한 사용량 분석
- 실패에 대한 환불 처리

🔧 **관리자 통합**

- Laravel Orchid 플랫폼 지원
- 종합적인 CRUD 인터페이스
- 실시간 모니터링 대시보드
- 상세한 보고 기능

## 시스템 요구사항

- PHP 8.1 이상
- Laravel 10.0 또는 11.0
- Laravel Orchid 14.0 이상

## 설치

Composer를 통해 패키지를 설치합니다:

```bash
composer require Techigh/credit-messaging
```

마이그레이션 파일을 게시하고 실행합니다:

```bash
php artisan vendor:publish --tag="credit-messaging-migrations"
php artisan migrate
```

설정 파일을 게시합니다:

```bash
php artisan vendor:publish --tag="credit-messaging-config"
```

## 환경 설정

`.env` 파일에 다음 환경 변수를 추가합니다:

```env
# 메시지 서비스 API 설정
ALIMTALK_API_URL="https://api.alimtalk-service.com"
ALIMTALK_API_KEY="your-alimtalk-api-key"

SMS_API_URL="https://api.sms-service.com"
SMS_API_KEY="your-sms-api-key"

LMS_API_URL="https://api.lms-service.com"
LMS_API_KEY="your-lms-api-key"

MMS_API_URL="https://api.mms-service.com"
MMS_API_KEY="your-mms-api-key"

# 웹훅 설정
CREDIT_MESSAGING_WEBHOOK_SECRET="your-webhook-secret"
```

## 빠른 시작

### 1. 데모 데이터 시딩

```bash
php artisan credit-messaging:seed
```

### 2. 기본 사용법

```php
use Techigh\CreditMessaging\Facades\CreditManager;
use Techigh\CreditMessaging\Facades\MessageRouter;
use Techigh\CreditMessaging\Models\CreditMessage;

// 사이트 크레딧 정보 조회
$siteCredit = CreditManager::getSiteCredit('site_001');

// 현재 잔액 확인
$balance = CreditManager::getBalance('site_001');

// 메시지 생성 및 발송
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

## API 참조

### CreditManager 파사드

```php
// 사이트 크레딧 설정 조회
$siteCredit = CreditManager::getSiteCredit(string $siteId);

// 현재 잔액 조회
$balance = CreditManager::getBalance(string $siteId);

// 크레딧 차감
$usage = CreditManager::chargeCredits(string $siteId, float $amount, array $metadata = []);

// 크레딧 환불
$refund = CreditManager::refundCredits(SiteCreditUsage $usage, float $amount, string $reason);

// 결제 추가
$payment = CreditManager::addPayment(string $siteId, float $amount, string $method, array $data = []);

// 결제 완료
$success = CreditManager::completePayment(SiteCreditPayment $payment);

// 사용량 통계 조회
$stats = CreditManager::getUsageStats(string $siteId, $startDate, $endDate);
```

### MessageRouter 파사드

```php
// 즉시 메시지 발송
$result = MessageRouter::sendMessage(CreditMessage $creditMessage);

// 메시지 예약 발송
$success = MessageRouter::scheduleMessage(CreditMessage $creditMessage);

// 메시지 비용 추정
$estimation = MessageRouter::estimateMessageCost(string $siteId, string $messageType, int $recipientCount, string $content = null);

// 배치 상태 조회
$status = MessageRouter::getBatchStatus(string $batchId);

// 웹훅 처리 (자동 호출됨)
$result = MessageRouter::processWebhook(string $provider, array $payload);
```

## 데이터베이스 스키마

패키지는 다음 테이블들을 생성합니다:

- `site_credits` - 크레딧 잔액 및 가격 설정
- `site_credit_payments` - 결제 및 충전 기록
- `site_credit_usages` - 크레딧 사용량 추적
- `credit_messages` - 메시지 캠페인 및 템플릿
- `message_send_logs` - 전송 로그 및 정산 데이터

## Orchid 관리자 통합

Laravel Orchid를 사용하는 경우, 패키지는 다음 관리자 화면을 제공합니다:

- `/admin/credit-messages` - 메시지 캠페인 관리
- `/admin/site-credits` - 크레딧 잔액 관리
- `/admin/site-credit-payments` - 결제 추적
- `/admin/site-credit-usages` - 사용량 모니터링
- `/admin/message-send-logs` - 전송 로그 보기

## 웹훅 처리

패키지는 메시지 서비스 제공업체의 웹훅을 자동으로 처리합니다:

```
POST /api/credit-messaging/webhook/{provider}
```

지원되는 제공업체: `alimtalk`, `sms`, `lms`, `mms`

## 큐 설정

최적의 성능을 위해 예약된 메시지를 처리하도록 큐를 설정합니다:

```bash
php artisan queue:work --queue=credit-messaging
```

## 메시지 라우팅 전략

### 1. 알림톡 우선 (alimtalk_first)

- 먼저 알림톡 전송을 시도합니다
- 실패 시 자동으로 SMS로 폴백합니다
- 비즈니스 메시지에 적합합니다

### 2. SMS 전용 (sms_only)

- 직접 SMS/LMS/MMS로 발송합니다
- 메시지 길이에 따라 자동으로 유형이 결정됩니다
- 신속한 전송이 필요한 경우에 사용합니다

### 3. 비용 최적화 (cost_optimized)

- 사용 가능한 옵션 중 가장 저렴한 방법을 선택합니다
- 비용 효율성이 중요한 마케팅 메시지에 적합합니다

## 크레딧 관리

### 자동 충전 설정

```php
$siteCredit = CreditManager::getSiteCredit('site_001');
$siteCredit->update([
    'auto_charge_enabled' => true,
    'auto_charge_threshold' => 10000.00, // 10,000원 이하일 때
    'auto_charge_amount' => 50000.00,    // 50,000원 충전
]);
```

### 수동 충전

```php
$payment = CreditManager::addPayment('site_001', 100000.00, 'credit_card', [
    'transaction_id' => 'txn_123456789'
]);

// 결제 성공 후
CreditManager::completePayment($payment);
```

## 사용량 모니터링

### 실시간 통계

```php
$stats = CreditManager::getUsageStats('site_001', now()->subDays(30), now());

/*
결과:
[
    'total_cost' => 150000.00,
    'message_counts' => [
        'alimtalk' => 500,
        'sms' => 1200,
        'lms' => 300,
        'mms' => 50
    ],
    'success_rate' => 98.5,
    'refund_amount' => 2500.00
]
*/
```

### 메시지별 상세 정보

```php
$message = CreditMessage::find(1);
echo "성공률: " . $message->success_rate . "%";
echo "실제 비용: " . number_format($message->actual_cost) . "원";
```

## 에러 처리 및 로깅

시스템은 포괄적인 로깅을 제공합니다:

```php
// 발송 시작
Log::info('메시지 발송 시작', [
    'credit_message_id' => $creditMessage->id,
    'site_id' => $creditMessage->site_id,
    'recipient_count' => count($creditMessage->recipients)
]);

// 발송 완료
Log::info('메시지 발송 완료', [
    'credit_message_id' => $creditMessage->id,
    'success_count' => $successCount,
    'failed_count' => $failedCount,
    'actual_cost' => $actualCost
]);
```

## 보안 고려사항

### 웹훅 서명 검증

```php
// config/credit-messaging.php
'webhook' => [
    'secret' => env('CREDIT_MESSAGING_WEBHOOK_SECRET'),
    'signature_header' => 'X-Webhook-Signature',
]
```

### API 요율 제한

```php
// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    // API 라우트들
});
```

## 테스트

패키지 테스트를 실행합니다:

```bash
composer test
```

테스트 환경 설정:

```bash
cp .env.example .env.testing
php artisan config:clear
php artisan migrate --env=testing
```

## 변경 로그

최근 변경 사항에 대한 자세한 내용은 [CHANGELOG](CHANGELOG.md)를 참조하세요.

## 기여하기

기여 방법에 대한 자세한 내용은 [CONTRIBUTING](CONTRIBUTING.md)를 참조하세요.

## 보안 취약점

보안 취약점을 신고하는 방법은 [보안 정책](../../security/policy)을 검토해 주세요.

## 크레딧

- [Techigh](https://github.com/Techigh)
- [모든 기여자](../../contributors)

## 라이선스

MIT 라이선스 (MIT). 자세한 내용은 [라이선스 파일](LICENSE.md)을 참조하세요. 