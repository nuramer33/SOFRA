<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Only ids: card details never touch Sofra, they live at Stripe.
            $table->string('stripe_customer_id', 64)->nullable()->unique()->after('paid_until');
            $table->string('stripe_subscription_id', 64)->nullable()->after('stripe_customer_id');
            // True while a Stripe subscription renews by itself (false once it is cancelled).
            $table->boolean('auto_renew')->default(false)->after('stripe_subscription_id');
            // Set when the last automatic charge failed; cleared by the next successful payment.
            $table->timestamp('payment_failed_at')->nullable()->after('auto_renew');
        });

        // Every Stripe event is processed once, even if Stripe delivers it twice.
        Schema::create('stripe_events', function (Blueprint $table) {
            $table->string('id', 80)->primary(); // evt_…
            $table->string('type', 80);
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');

        Schema::table('shops', function (Blueprint $table) {
            $table->dropUnique(['stripe_customer_id']);
            $table->dropColumn(['stripe_customer_id', 'stripe_subscription_id', 'auto_renew', 'payment_failed_at']);
        });
    }
};
