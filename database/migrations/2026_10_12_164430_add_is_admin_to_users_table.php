<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Platform staff. Only ever set by `php artisan sofra:admin`.
            $table->boolean('is_admin')->default(false)->after('locale');

            // A platform admin doesn't have to own a shop.
            $table->foreignId('shop_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
