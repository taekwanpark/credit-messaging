# Techigh Credit Messaging

**언어 선택:** [English](README.md) | [한국어](README-ko.md)

**문서:** [아키텍처 가이드](ARCHITECTURE.md)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Techigh/credit-messaging.svg?style=flat-square)](https://packagist.org/packages/Techigh/credit-messaging)
[![Total Downloads](https://img.shields.io/packagist/dt/Techigh/credit-messaging.svg?style=flat-square)](https://packagist.org/packages/Techigh/credit-messaging)
[![License](https://img.shields.io/packagist/l/Techigh/credit-messaging.svg?style=flat-square)](https://packagist.org/packages/Techigh/credit-messaging)

멀티테넌트 지원, 스마트 라우팅, 자동 정산 기능을 갖춘 Laravel용 종합 크레딧 기반 메시징 시스템입니다.

## 주요 기능

🏦 **크레딧 관리**
- 사이트별 크레딧 잔액 관리
- 메시지 유형별 유연한 요금제
- 설정 가능한 임계값을 통한 자동 충전
- 다중 결제 게이트웨이를 통한 결제 추적
- 실패한 메시지에 대한 자동 환불

📱 **메시지 라우팅**
- **알림톡 우선**: 알림톡 시도 후 SMS로 대체
- **SMS 전용**: 직접 SMS 발송
- **비용 최적화**: 가장 저렴한 옵션 선택
- 큐 처리를 통한 **예약 메시징**

💰 **정산 및 청구**
- 실시간 비용 추적
- 전송 결과 기반 자동 정산
- 상세한 사용량 분석
- 실패 시 환불 처리

🔧 **관리자 통합**
- Laravel Orchid 플랫폼 지원
- 종합적인 CRUD 인터페이스
- 실시간 모니터링 대시보드
- 상세한 리포팅 기능

## 시스템 요구사항

- PHP 8.1 이상
- Laravel 10.0 또는 11.0
- Laravel Orchid 14.0 이상

## 설치 방법

Composer를 통해 패키지를 설치합니다:

```bash
composer require Techigh/credit-messaging
```

마이그레이션을 발행하고 실행합니다:

```bash
php artisan vendor:publish --tag="credit-messaging-migrations"
php artisan migrate
```

설정 파일을 발행합니다:

```bash
php artisan vendor:publish --tag="credit-messaging-config"
```

## 설정

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

### 1. 데모 데이터 시드

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

### CreditManager Facade

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

### MessageRouter Facade

```php
// 즉시 메시지 발송
$result = MessageRouter::sendMessage(CreditMessage $creditMessage);

// 메시지 예약 발송
$success = MessageRouter::scheduleMessage(CreditMessage $creditMessage);

// 메시지 비용 예상
$estimation = MessageRouter::estimateMessageCost(string $siteId, string $messageType, int $recipientCount, string $content = null);

// 배치 상태 조회
$status = MessageRouter::getBatchStatus(string $batchId);

// 웹훅 처리 (자동 호출됨)
$result = MessageRouter::processWebhook(string $provider, array $payload);
```

## 데이터베이스 스키마

이 패키지는 다음 테이블들을 생성합니다:

- `site_credits` - 크레딧 잔액 및 요금 설정
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
- `/admin/message-send-logs` - 전송 로그 조회

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

## 테스트

패키지 테스트를 실행합니다:

```bash
composer test
```

## 변경 로그

최근 변경 사항에 대한 자세한 정보는 [CHANGELOG](CHANGELOG.md)를 참조하세요.

## 기여하기

자세한 내용은 [CONTRIBUTING](CONTRIBUTING.md)를 참조하세요.

## 보안 취약점

보안 취약점 신고 방법은 [보안 정책](../../security/policy)을 검토하세요.

## 크레딧

- [Techigh](https://github.com/Techigh)
- [모든 기여자](../../contributors)

## 라이선스

MIT 라이선스(MIT). 자세한 내용은 [라이선스 파일](LICENSE.md)을 참조하세요. 