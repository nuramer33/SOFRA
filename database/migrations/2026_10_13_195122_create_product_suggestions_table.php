<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Goes well with": an owner links a main product to a complementary one,
 * optionally with a discount that applies when both are in the same order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('suggested_product_id')->constrained('products')->cascadeOnDelete();
            $table->json('title')->nullable();       // {"ar": "...", "tr": "...", "en": "..."}
            $table->json('description')->nullable();
            $table->string('discount_type', 10)->nullable(); // percent | fixed | null = no discount
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'suggested_product_id']);
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_suggestions');
    }
};
