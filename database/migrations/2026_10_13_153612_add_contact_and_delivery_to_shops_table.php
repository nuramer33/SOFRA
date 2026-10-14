<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->json('address')->nullable()->after('tagline'); // translatable
            $table->json('hours')->nullable()->after('address'); // translatable, free text
            $table->string('maps_url', 500)->nullable()->after('hours');
            $table->string('instagram', 30)->nullable()->after('maps_url'); // handle, no "@"
            $table->decimal('delivery_fee', 10, 2)->nullable()->after('order_types');
            $table->decimal('min_order', 10, 2)->nullable()->after('delivery_fee'); // for delivery orders
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['address', 'hours', 'maps_url', 'instagram', 'delivery_fee', 'min_order']);
        });
    }
};
