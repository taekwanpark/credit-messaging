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
        Schema::create('site_credit_usages', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('site_id')->index()->comment('사이트/테넌트 식별자');
            
            $table->enum('message_type', ['alimtalk', 'sms', 'lms', 'mms'])->comment('메시지 타입');
            $table->integer('quantity')->comment('전송 수량');
            $table->decimal('cost_per_unit', 8, 2)->comment('단가');
            $table->decimal('total_cost', 10, 2)->comment('총 비용');
            
            // 환불 관련
            $table->decimal('refund_amount', 10, 2)->default(0.00)->comment('환부금액');
            $table->text('refund_reason')->nullable()->comment('환불 사유');
            $table->timestamp('refunded_at')->nullable()->comment('환부시간');
            
            // 메타 데이터
            $table->string('batch_id')->nullable()->comment('일괄 전송 배치 ID');
            $table->json('metadata')->nullable()->comment('추가 메타데이터');
            
            // 상태 추적
            $table->enum('status', ['reserved', 'used', 'refunded', 'failed'])->default('reserved')->comment('사용 상태');
            
            $table->unsignedBigInteger('sort_order')->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['site_id', 'status']);
            $table->index(['site_id', 'message_type']);
            $table->index('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_credit_usages');
    }
};
