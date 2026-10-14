<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use App\Support\ImageStore;
use App\Tenancy\BelongsToShop;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

// Note: shop_id is intentionally NOT fillable — BelongsToShop sets it.
#[Fillable([
    'category_id', 'name', 'description', 'price', 'compare_at_price',
    'image_path', 'is_available', 'is_featured', 'sort_order',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToShop, HasFactory, HasTranslations, Sortable;

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            app(ImageStore::class)->delete($product->image_path);
            AnalyticsDaily::query()->where('product_id', $product->id)->delete();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(ProductSuggestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function imageUrl(string $variant = 'lg'): ?string
    {
        return ImageStore::url($this->image_path, $variant);
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && (float) $this->compare_at_price > (float) $this->price;
    }

    /**
     * When moving to another category, the product goes to the end of it.
     */
    public function moveToCategory(int $categoryId): void
    {
        if ($this->category_id === $categoryId) {
            return;
        }

        $this->category_id = $categoryId;
        $this->sort_order = (int) $this->sortableSiblings()->max('sort_order') + 1;
    }

    protected function sortableSiblings(): Builder
    {
        return static::query()->where('category_id', $this->category_id);
    }
}
