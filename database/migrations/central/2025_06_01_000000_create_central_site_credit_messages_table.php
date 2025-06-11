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
        if (!Schema::hasTable('site_plans')) {
            Schema::create('site_plans', function (Blueprint $table) {
                $table->id();
                $table->uuid()->unique();
                $table->json('title')->nullable()->comment('title for multi language');

                $table->decimal('cost_per_credit')->default(config('credit-messaging.default_credit_costs.cost_per_credit'));
                $table->decimal('alimtalk_credits_cost')->default(config('credit-messaging.default_credit_costs.alimtalk'));
                $table->decimal('sms_credits_cost')->default(config('credit-messaging.default_credit_costs.sms'));
                $table->decimal('lms_credits_cost')->default(config('credit-messaging.default_credit_costs.lms'));
                $table->decimal('mms_credits_cost')->default(config('credit-messaging.default_credit_costs.mms'));

                $table->integer('sort_order')->unsigned()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('site_plan_id')->nullable()->after('data')->constrained('site_plans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['site_plan_id']);
            $table->dropColumn('site_plan_id');
        });
        Schema::dropIfExists('site_plans');
    }
};
