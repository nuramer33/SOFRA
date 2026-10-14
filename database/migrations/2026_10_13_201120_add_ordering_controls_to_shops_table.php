<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // "Pause new orders" switch: the menu stays visible, ordering is closed.
            $table->boolean('orders_paused')->default(false)->after('order_types');
            // Weekly ordering hours: {"0": {"open": true, "from": "09:00", "to": "23:00"}, …}
            // (0 = Sunday). NULL means "no schedule: always open".
            $table->json('order_hours')->nullable()->after('orders_paused');
            $table->string('timezone', 40)->default('Europe/Istanbul')->after('order_hours');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['orders_paused', 'order_hours', 'timezone']);
        });
    }
};
