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
        Schema::create('message_send_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('usage_id')->constrained('site_credit_usages')->onDelete('cascade')->comment('크레딧 사용 내역 ID');
            
            // 메시지 전송 정보
            $table->json('message_ids')->comment('메시지 서비스에서 반환한 메시지 ID 목록');
            $table->enum('message_type', ['alimtalk', 'sms', 'lms', 'mms'])->comment('전송된 메시지 타입');
            $table->integer('total_count')->comment('전송 대상 수');
            $table->integer('success_count')->default(0)->comment('성공 수');
            $table->integer('failed_count')->default(0)->comment('실패 수');
            
            // 웹훅 결과
            $table->json('webhook_result')->nullable()->comment('웹훅으로 받은 전송 결과');
            $table->timestamp('webhook_received_at')->nullable()->comment('웹훅 수신 시간');
            
            // 정산 상태
            $table->enum('settlement_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->comment('정산 상태');
            $table->decimal('final_cost', 10, 2)->nullable()->comment('최종 정산 비용');
            $table->timestamp('settled_at')->nullable()->comment('정산 완료 시간');
            
            // 에러 추적
            $table->text('error_message')->nullable()->comment('에러 메시지');
            $table->integer('retry_count')->default(0)->comment('재시도 횟수');
            
            $table->unsignedBigInteger('sort_order')->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['settlement_status', 'created_at']);
            $table->index('webhook_received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_send_logs');
    }
};
