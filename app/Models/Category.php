<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use App\Support\ImageStore;
use App\Tenancy\BelongsToShop;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

// Note: shop_id is intentionally NOT fillable — BelongsToShop sets it.
#[Fillable(['name', 'image_path', 'sort_order', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use BelongsToShop, HasFactory, HasTranslations, Sortable;

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The database cascade would remove products silently, leaving their
     * image files behind. Deleting through Eloquent cleans up the files.
     */
    protected static function booted(): void
    {
        static::deleting(function (Category $category) {
            $category->products()->get()->each->delete();
            app(ImageStore::class)->delete($category->image_path);
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort_order');
    }

    public function imageUrl(string $variant = 'lg'): ?string
    {
        return ImageStore::url($this->image_path, $variant);
    }

    protected function sortableSiblings(): Builder
    {
        return static::query()->where('shop_id', $this->shop_id);
    }
}
