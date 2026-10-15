<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One administrator action on a shop's subscription. Written only by the
 * admin panel code (never from a form an owner can submit).
 */
#[Fillable(['shop_id', 'user_id', 'type', 'amount'])]
class SubscriptionEvent extends Model
{
    public const PAYMENT = 'payment';

    public const EXTENSION = 'extension';

    public const SUSPENSION = 'suspension';

    public const ACTIVATION = 'activation';

    public const PASSWORD_RESET = 'password_reset';

    // Written by the Stripe webhook (no administrator involved).
    public const SUBSCRIBED = 'subscribed';

    public const ONLINE_PAYMENT = 'online_payment';

    public const PAYMENT_FAILED = 'payment_failed';

    public const CANCELLED = 'cancelled';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
