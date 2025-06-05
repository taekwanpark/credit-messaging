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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_credit_usages');
        Schema::dropIfExists('site_campaign_messages');
        Schema::dropIfExists('site_campaigns');
        Schema::dropIfExists('site_credits');
    }
};
