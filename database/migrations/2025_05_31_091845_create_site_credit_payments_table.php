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
        Schema::create('site_credit_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('site_id')->index()->comment('사이트/테넌트 식별자');
            
            $table->decimal('amount', 10, 2)->comment('충전 금액');
            $table->enum('payment_method', ['card', 'bank', 'virtual', 'admin'])->comment('결제 방법');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending')->comment('결제 상태');
            
            $table->string('transaction_id')->nullable()->comment('외부 거래 ID');
            $table->string('payment_gateway')->nullable()->comment('결제 게이트웨이');
            $table->json('payment_data')->nullable()->comment('결제 상세 데이터');
            
            $table->text('notes')->nullable()->comment('관리자 메모');
            $table->timestamp('completed_at')->nullable()->comment('충전 완료 시간');
            
            $table->unsignedBigInteger('sort_order')->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['site_id', 'status']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_credit_payments');
    }
};
