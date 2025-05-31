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
        Schema::create('credit_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->json('title')->nullable()->comment('title for multi language');
            $table->string('site_id')->index()->comment('사이트/테넌트 식별자');
            
            // 메시지 전송 설정
            $table->enum('message_type', ['alimtalk', 'sms', 'lms', 'mms'])->comment('메시지 타입');
            $table->enum('routing_strategy', ['alimtalk_first', 'sms_only', 'cost_optimized'])->default('alimtalk_first')->comment('라우팅 전략');
            
            // 메시지 내용
            $table->text('message_content')->comment('메시지 내용');
            $table->json('recipients')->comment('수신자 목록 (전화번호 배열)');
            
            // 전송 상태
            $table->enum('status', ['draft', 'scheduled', 'sending', 'completed', 'failed', 'cancelled'])->default('draft')->comment('전송 상태');
            $table->timestamp('scheduled_at')->nullable()->comment('예약 전송 시간');
            $table->timestamp('sent_at')->nullable()->comment('실제 전송 시간');
            
            // 비용 정보
            $table->decimal('estimated_cost', 10, 2)->nullable()->comment('예상 비용');
            $table->decimal('actual_cost', 10, 2)->nullable()->comment('실제 비용');
            
            // 결과 통계
            $table->integer('total_recipients')->default(0)->comment('총 수신자 수');
            $table->integer('success_count')->default(0)->comment('성공 수');
            $table->integer('failed_count')->default(0)->comment('실패 수');
            
            $table->unsignedBigInteger('sort_order')->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['site_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_messages');
    }
};
