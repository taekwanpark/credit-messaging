<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_credits', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('site_id')->index()->comment('사이트/테넌트 식별자');
            
            // 메시지 타입별 단가 (KRW 원 단위)
            $table->decimal('alimtalk_cost', 8, 2)->default(0.00)->comment('알림톡 단가');
            $table->decimal('sms_cost', 8, 2)->default(0.00)->comment('SMS 단가');
            $table->decimal('lms_cost', 8, 2)->default(0.00)->comment('LMS 단가');
            $table->decimal('mms_cost', 8, 2)->default(0.00)->comment('MMS 단가');
            
            // 현재 잔액
            $table->decimal('balance', 10, 2)->default(0.00)->comment('현재 크레딧 잔액');
            
            // 설정
            $table->boolean('auto_charge_enabled')->default(false)->comment('자동 충전 활성화');
            $table->decimal('auto_charge_threshold', 10, 2)->nullable()->comment('자동 충전 임계값');
            $table->decimal('auto_charge_amount', 10, 2)->nullable()->comment('자동 충전 금액');
            
            $table->unsignedBigInteger('sort_order')->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_credits');
    }
};
