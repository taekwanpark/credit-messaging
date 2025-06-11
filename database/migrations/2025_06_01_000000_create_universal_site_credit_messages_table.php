<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_credits', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->json('title')->nullable()->comment('title for multi language');
            $table->char('order_id', 25)->index();

            $table->string('type')->default('CHARGE');
            $table->string('status', 50)->default('PENDING');

            $table->decimal('purchase_amount', 14, 2)->default(0)->comment('구입한 가격');
            $table->decimal('credits_amount', 14, 2)->default(0)->comment('지급된 크레딧');
            $table->decimal('used_credits', 14, 2)->default(0)->comment('사용한 크레딧');
            $table->decimal('balance_credits', 14, 2)->default(0)->comment('사용 가능한 잔여 크레딧');
            $table->decimal('cost_per_credit', 14, 2)->default(8)->comment('구입 당시 1크레딧 가격');

            $table->decimal('sms_credits_cost')->default(1.5)->comment('구입 당시 책정된 sms 사용시 차감 크레딧'); // 12
            $table->decimal('lms_credits_cost')->default(4.5)->comment('구입 당시 책정된 lms 사용시 차감 크레딧'); // 36
            $table->decimal('mms_credits_cost')->default(12)->comment('구입 당시 책정된 mms 사용시 차감 크레딧'); // 96
            $table->decimal('alimtalk_credits_cost')->default(1)->comment('구입 당시 책정된 kakao 알림톡 사용시 차감 크레딧'); // 8

            $table->integer('sort_order')->unsigned()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('site_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->json('title')->nullable()->comment('title for multi language');

            $table->string('type')->default('alimtalk')->comment('alimtalk, sms, lms, mms');
            $table->string('status')->default('PENDING')->comment('PENDING, PROGRESS, SUCCESS, FAILED, CANCELLED');

            $table->string('template_code', 30)->nullable();

            $table->boolean('replace_sms')->default(true);
            $table->string('sms_title', 40)->nullable();
            $table->string('sms_content', 2000)->nullable();

            $table->integer('total_count')->default(0);
            $table->integer('pending_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('canceled_count')->default(0);
            $table->integer('rejected_count')->default(0);
            $table->integer('failed_count')->default(0);

            $table->integer('sms_success_count')->default(0);
            $table->integer('sms_failed_count')->default(0);

            $table->timestamp('send_at')->index();
            $table->timestamp('webhook_received_at')->nullable()->comment('웹훅 수신 시간');

            $table->integer('sort_order')->unsigned()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('site_campaign_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->json('title')->nullable()->comment('title for multi language');

            $table->foreignId('site_campaign_id')->constrained('site_campaigns');

            $table->string('phone_e164', 16);
            $table->string('name')->nullable();

            $table->string('kakao_status')->nullable();
            $table->string('sms_status')->nullable();
            $table->string('kakao_result_code')->nullable();
            $table->string('sms_result_code')->nullable();

            $table->integer('sort_order')->unsigned()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('site_credit_usages', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->json('title')->nullable()->comment('title for multi language');

            $table->foreignId('site_credit_id')->constrained('site_credits')->cascadeOnDelete();
            $table->foreignId('site_campaign_id')->constrained('site_campaigns')->cascadeOnDelete();
            $table->tinyInteger('type')->default(1)->comment('1: 차감(DEDUCT), -1: 환급(REFUND)');
            $table->string('credit_type')->default('sms'); // sms, lms, kakao_fr 등
            $table->integer('used_count')->default(0)->comment('사용된 건수');
            $table->decimal('used_credits', 14, 2)->default(0)->comment('사용된 크레딧');
            $table->decimal('used_cost', 14, 2)->default(0)->comment('사용 금액');

            $table->integer('sort_order')->unsigned()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('site_credit_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->json('title')->nullable()->comment('title for multi language');
            $table->foreignId('site_credit_id')->constrained('site_credits');

            $table->string('m_id')->nullable()->comment('상점 ID');
            $table->string('last_transaction_key')->nullable()->comment('마지막 거래 키');
            $table->string('payment_key')->nullable()->comment('결제 키');
            $table->string('secret')->nullable()->comment('비밀 키');
            $table->string('country')->nullable()->comment('국가');
            $table->string('version')->nullable()->comment('버전');
            // 주문
            $table->string('order_id')->nullable()->comment('주문 ID');
            $table->string('order_name')->nullable()->comment('주문 이름');
            // 결제
            $table->string('type')->nullable()->comment('결제 타입');
            $table->string('status')->nullable()->comment('결제 상태');
            $table->string('method')->nullable()->comment('결제 방법');
            $table->timestamp('requested_at')->nullable()->comment('요청 일시');
            $table->timestamp('approved_at')->nullable()->comment('승인 일시');
            $table->boolean('use_escrow')->nullable()->comment('에스크로 사용 여부');
            $table->boolean('culture_expense')->nullable()->comment('문화비 여부');
            $table->boolean('is_partial_cancelable')->nullable()->comment('부분 취소 가능 여부');
            // 금액
            $table->string('currency')->nullable()->comment('통화');
            $table->integer('total_amount')->nullable()->comment('총 금액');
            $table->integer('balance_amount')->nullable()->comment('잔액');
            $table->integer('supplied_amount')->nullable()->comment('공급가액');
            $table->integer('vat')->nullable()->comment('부가가치세');
            $table->integer('tax_free_amount')->nullable()->comment('비과세 금액');
            $table->integer('tax_exemption_amount')->nullable()->comment('면세 금액');
            $table->jsonb('card')->nullable()->comment('카드 정보');
            $table->jsonb('easy_pay')->nullable()->comment('간편 결제 정보');
            $table->jsonb('virtual_account')->nullable()->comment('가상 계좌 정보');
            $table->jsonb('transfer')->nullable()->comment('계좌 이체 정보');
            $table->jsonb('mobile_phone')->nullable()->comment('휴대폰 결제 정보');
            $table->jsonb('gift_certificate')->nullable()->comment('상품권 정보');
            $table->jsonb('cash_receipt')->nullable()->comment('현금 영수증 정보');
            $table->jsonb('cash_receipts')->nullable()->comment('현금 영수증들');
            $table->jsonb('discount')->nullable()->comment('할인 정보');
            $table->jsonb('cancels')->nullable()->comment('취소 정보');
            $table->jsonb('failure')->nullable()->comment('실패 정보');
            // 영수증 및 체크아웃 URL
            $table->string('receipt_url')->nullable()->comment('영수증 URL');
            $table->string('checkout_url')->nullable()->comment('체크아웃 URL');
            $table->longText('memo')->nullable();
            $table->integer('sort_order')->unsigned()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('site_credit_payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('pg')->default('TOSS_PAYMENT')->comment('pg');
            $table->string('event_type')->comment('event type');
            $table->string('type')->comment('event type');
            $table->jsonb('data')->comment('event data');
            $table->timestamp('created_at')->comment('event created at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_credit_payment_webhooks');
        Schema::dropIfExists('site_credit_payments');
        Schema::dropIfExists('site_credit_usages');
        Schema::dropIfExists('site_campaign_messages');
        Schema::dropIfExists('site_campaigns');
        Schema::dropIfExists('site_credits');
    }
};
