<?php

namespace App\Models;

use App\Tenancy\BelongsToShop;
use Database\Factories\ProductSuggestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A complementary product offered next to a main one, optionally discounted
 * when both are ordered together. Never advertised without a valid discount.
 */
// Note: shop_id is intentionally NOT fillable — BelongsToShop sets it.
#[Fillable(['product_id', 'suggested_product_id', 'title', 'description', 'discount_type', 'discount_value', 'is_active', 'sort_order'])]
class ProductSuggestion extends Model
{
    /** @use HasFactory<ProductSuggestionFactory> */
    use BelongsToShop, HasFactory, HasTranslations;

    public const DISCOUNT_PERCENT = 'percent';

    public const DISCOUNT_FIXED = 'fixed';

    public const DISCOUNT_TYPES = [self::DISCOUNT_PERCENT, self::DISCOUNT_FIXED];

    public const MAX_PERCENT = 90;

    public array $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function suggestedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'suggested_product_id');
    }

    /**
     * What one unit of the suggested product is reduced by (0 = no valid discount).
     * Always computed from the current price, and never more than the price itself.
     */
    public function discountPerUnit(float $price): float
    {
        $value = (float) $this->discount_value;

        $amount = match ($this->discount_type) {
            self::DISCOUNT_PERCENT => $value > 0 && $value <= self::MAX_PERCENT ? round($price * $value / 100, 2) : 0.0,
            self::DISCOUNT_FIXED => $value > 0 ? min($value, $price) : 0.0,
            default => 0.0,
        };

        return round(max(0.0, $amount), 2);
    }

    public function hasValidDiscount(float $price): bool
    {
        return $this->discountPerUnit($price) > 0;
    }
}
