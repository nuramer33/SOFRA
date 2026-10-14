<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Which order types the shop accepts: dine_in, pickup, delivery.
            // NULL = all of them.
            $table->json('order_types')->nullable()->after('whatsapp_number');

            // Set the first time the owner downloads/prints their QR code.
            $table->timestamp('qr_downloaded_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['order_types', 'qr_downloaded_at']);
        });
    }
};
