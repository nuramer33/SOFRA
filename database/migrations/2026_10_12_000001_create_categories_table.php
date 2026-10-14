<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shop_id', 'sort_order']);

            // Lets products reference (id, shop_id) together — see products migration.
            $table->unique(['id', 'shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
