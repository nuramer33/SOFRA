<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('category_id');
            $table->json('name');
            $table->json('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable(); // "was" price for offers
            $table->string('image_path')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['shop_id', 'category_id', 'sort_order']);

            // Composite foreign key: the category must belong to the SAME shop.
            // Even if application code had a bug, the database itself refuses
            // a product that points at another shop's category.
            $table->foreign(['category_id', 'shop_id'])
                ->references(['id', 'shop_id'])->on('categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
