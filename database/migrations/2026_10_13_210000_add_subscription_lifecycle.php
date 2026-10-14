<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Last reminder email sent, e.g. "2026-12-12:3": makes the daily job idempotent.
            $table->string('last_billing_notice', 40)->nullable()->after('paid_until');
        });

        // What an administrator did to a subscription (and when): payments, extensions, suspensions.
        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);          // payment | extension | suspension | activation
            $table->unsignedSmallInteger('amount')->nullable(); // months (payment) or days (extension)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['shop_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_events');

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('last_billing_notice');
        });
    }
};
