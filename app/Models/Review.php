<?php

namespace App\Models;

use App\Support\ImageStore;
use App\Tenancy\BelongsToShop;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Note: shop_id is intentionally NOT fillable — BelongsToShop sets it.
#[Fillable(['rating', 'comment', 'name', 'contact', 'photo_path', 'locale'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use BelongsToShop, HasFactory;

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    /**
     * Deleting through Eloquent also removes the photo file.
     */
    protected static function booted(): void
    {
        static::deleting(fn (Review $review) => app(ImageStore::class)->delete($review->photo_path));
    }

    public function photoUrl(string $variant = 'lg'): ?string
    {
        return ImageStore::url($this->photo_path, $variant);
    }
}
