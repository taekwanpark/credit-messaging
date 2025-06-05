다음은 **`cms-orbit`** 프로젝트의 테넌트에서 **크레딧 기반 알림톡 발송 패키지 프로세스**를 마크다운 형식으로 정리한 문서입니다. 현재 구성 중인 `packages/techigh/credit-messaging` 패키지 기준으로 작성되었으며, **Orchid 기반 관리자 전용 사용**, **프로젝트의 `payment` 테이블을 크레딧 구매에 활용**하는 것을 전제로 구성했습니다.

해당 패키지는 다른 프로젝트 내에서 사용될 예정이고
메시지 플랫폼 이라고 하는건 지금 현재 프로젝트(smpp-provider)야

---

# 📦 cms-orbit 알림톡 발송 패키지 프로세스

## 🧾 1. 크레딧 구매

* 테넌트가 `site_credit`을 구매
* 결제 정보는 **프로젝트 공통 `payments` 테이블** 사용
* `alimtalk_credit_cost`는 반드시 `sms`, `lms`, `mms`의 각 `credit_cost`보다 작거나 같아야 함
* 구매 시 해당 테넌트 전용 크레딧이 생성됨 (예: `credit_products`, `credits` 테이블 사용)

---

## 🚀 2. 발송 요청

* 사용자가 발송 요청을 Orchid 관리자 패널에서 수행
* 이후 내부 메시지 발송 플랫폼에서 처리됨

---

## ✅ 3. 크레딧 검증 로직

발송 전에 다음 조건을 검사:

1. **보유한 크레딧 존재 여부**
2. **`balance_credits` 합이 0 이상인지**
3. **충분한 잔여 크레딧 여부**
   조건은 대체 문자 여부에 따라 다름:

| 구분          | 계산 방식                                                 |
| ----------- | ----------------------------------------------------- |
| 대체 문자 사용 안함 | `alimtalk_credit_cost × message_count`                |
| 대체 문자 사용함   | `sms/lms/mms_credit_cost × message_count` (타입에 따라 선택) |

> ⛔ 잔여 크레딧이 부족할 경우 발송 불가

---

## ➖ 4. 크레딧 차감

* 위 계산 결과에 따라 적절한 크레딧을 **`credits` 테이블에서 balance\_credits 차감**
* 차감 내역은 `credit_histories` 테이블 등으로 기록

---

## 📝 5. 캠페인 및 개별 메시지 생성

* `campaigns` 테이블에 캠페인 생성
* 하위에 여러 건의 개별 메시지를 `campaign_messages` 혹은 유사 테이블로 저장

---

## 📡 6. 메시지 발송 (메시지 플랫폼)

* 메시지 플랫폼이 `알림톡` 발송 진행
* 대체 문자 여부에 따라 분기 처리:

| 조건        | 처리 내용                      |
| --------- | -------------------------- |
| 대체 문자 사용  | 알림톡 실패 시 SMS/LMS/MMS 자동 발송 |
| 대체 문자 미사용 | 실패 상태 그대로 종료               |

### 📊 발송 결과 저장 항목

* 알림톡 결과:

  * `total_count`, `pending_count`, `success_count`, `canceled_count`, `failed_count`, `rejected_count`
* 대체 문자 결과:

  * `sms_success_count`, `sms_failed_count`
  * 대체 문자 미사용 시 해당 값은 **0 고정**

---

## 🔁 7. 웹훅 통한 발송 결과 수신

* 외부 플랫폼 → 웹훅 수신 → `campaign_messages` 및 `campaigns` 결과 업데이트

---

## 💸 8. 크레딧 환급 및 추가 차감

순서는 반드시 **환급 → 차감** 순으로 처리.

### 🎯 환급 로직

| 조건          | 환급 계산식                                                  |
| ----------- | ------------------------------------------------------- |
| 대체 문자 사용 안함 | `(failed + canceled + rejected) × alimtalk_credit_cost` |
| 대체 문자 사용함   | `(success + sms_failed) × 해당 문자 credit_cost`            |

* 환급은 **새로운 `credits` 레코드**로 생성됨

### ➖ 차감 로직

* 대체 문자 사용한 경우:

  * `success_count × alimtalk_credit_cost` 차감

> 💡 최종 크레딧 사용량과 환급 내역은 `credit_histories` 테이블로 정산 추적 가능해야 함

---

## 📌 기타 구현 참고사항

* `packages/techigh/credit-messaging/database/migrations`는 정상 구조로 진행
* 각 `Entities` 별로 아래 파일 구성 필요:

  * `Model`
  * `Orchid Screen`
  * `Orchid Layout`
* `payment` 관련 처리는 Orchid 관리 UI에서 **결제 확인/연동**용 처리만 필요
* 모든 컨트롤러/외부 API 없이 내부만 사용할 예정 (관리자 UI 전용)

---

## 🎯 Facade 사용법

### MessageSend Facade 사용

이제 `MessageSendService`를 Facade로 편리하게 사용할 수 있습니다:

```php
<?php

use MessageSend;

// 알림톡 발송 요청
$result = MessageSend::sendAlimtalk([
    'type' => 'alimtalk',
    'templateCode' => 'TEMPLATE_CODE',
    'replaceSms' => true,
    'smsTitle' => '제목',
    'smsContent' => '대체 SMS 내용',
    'sendAt' => '2024-01-01 10:00:00', // 선택적
    'contacts' => [
        [
            'phone_e164' => '+821012345678',
            'name' => '홍길동'
        ],
        // ... 추가 연락처
    ]
]);
```

### 사용 가능한 메소드

- `sendAlimtalk(array $inputs)`: 알림톡 발송 처리

### 입력 파라미터

| 파라미터 | 타입 | 필수 | 설명 |
|----------|------|------|------|
| `type` | string | 선택 | 메시지 타입 ('alimtalk', 'sms', 'lms', 'mms') |
| `templateCode` | string | 조건부 | 알림톡 템플릿 코드 (type이 'alimtalk'일 때 필수) |
| `replaceSms` | boolean | 선택 | 대체 SMS 사용 여부 (기본값: true) |
| `smsTitle` | string | 조건부 | SMS 제목 (대체 SMS 사용 시 필수) |
| `smsContent` | string | 조건부 | SMS 내용 (대체 SMS 사용 시 필수) |
| `sendAt` | string | 선택 | 발송 예약 시간 (미지정 시 즉시 발송) |
| `contacts` | array | 필수 | 수신자 목록 |

### contacts 배열 구조

```php
[
    [
        'phone_e164' => '+821012345678', // E.164 형식 전화번호
        'name' => '홍길동'               // 수신자 이름
    ],
    // ... 추가 연락처
]
```

---
