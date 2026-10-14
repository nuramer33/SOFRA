<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Privacy-friendly analytics: one counter per shop, day, event and product.
 * No visitor, order, IP or device data is stored — only totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->string('event', 20);
            $table->unsignedBigInteger('product_id')->default(0); // 0 = not about a product
            $table->unsignedInteger('total')->default(0);

            $table->unique(['shop_id', 'day', 'event', 'product_id']);
            $table->index('day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_dailies');
    }
};
