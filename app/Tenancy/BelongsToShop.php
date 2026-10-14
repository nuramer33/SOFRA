<?php

namespace App\Tenancy;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Use on every model that has a `shop_id` column.
 *
 * 1. Reads are filtered to the current shop (ShopScope).
 * 2. New rows get the current shop's id automatically, whatever the form sent.
 * 3. A row can never be moved to another shop after it's created.
 */
trait BelongsToShop
{
    public static function bootBelongsToShop(): void
    {
        static::addGlobalScope(new ShopScope);

        static::creating(function (Model $model) {
            $tenant = app(CurrentShop::class);

            if ($tenant->check()) {
                $model->shop_id = $tenant->id();
            }
            // No current shop and no shop_id? The NOT NULL column makes the
            // insert fail. That's deliberate: fail closed, never guess.
        });

        static::updating(function (Model $model) {
            if ($model->isDirty('shop_id')) {
                throw new LogicException('A record cannot be moved to another shop.');
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
