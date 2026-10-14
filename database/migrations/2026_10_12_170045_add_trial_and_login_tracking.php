<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Free period end (registration + config('sofra.billing.trial_months')).
            $table->timestamp('trial_ends_at')->nullable()->after('qr_downloaded_at');
            // Set by an admin when a payment is received (no online payments in v1).
            $table->timestamp('paid_until')->nullable()->after('trial_ends_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        // Shops that already exist get their trial counted from their sign-up date.
        $months = (int) config('sofra.billing.trial_months', 2);
        DB::table('shops')->whereNull('trial_ends_at')->orderBy('id')->each(function ($shop) use ($months) {
            DB::table('shops')->where('id', $shop->id)->update([
                'trial_ends_at' => Carbon::parse($shop->created_at)->addMonths($months),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'paid_until']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });
    }
};
