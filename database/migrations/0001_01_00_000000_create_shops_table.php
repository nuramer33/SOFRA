<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A shop is the tenant. Every other business table points back here.
     *
     * Translatable fields (name, tagline) are JSON: {"ar": "...", "tr": "...", "en": "..."}.
     */
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('tagline')->nullable();
            $table->string('slug', 40)->unique();          // public link: /{slug}
            $table->string('logo_path')->nullable();
            $table->string('brand_color', 7)->default('#A8462A');
            $table->string('whatsapp_number', 20)->nullable(); // digits only, international format
            $table->string('currency', 3)->default('TRY');
            $table->string('default_locale', 5)->default('ar');
            $table->json('enabled_locales')->nullable();     // which languages the menu offers
            $table->string('template', 30)->default('classic');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
