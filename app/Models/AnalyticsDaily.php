<?php

namespace App\Models;

use App\Tenancy\BelongsToShop;
use Database\Factories\AnalyticsDailyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A daily counter (see App\Support\Analytics). Rows are written only by the
 * recorder; shop_id is intentionally NOT fillable — BelongsToShop sets it.
 */
#[Fillable(['day', 'event', 'product_id', 'total'])]
class AnalyticsDaily extends Model
{
    /** @use HasFactory<AnalyticsDailyFactory> */
    use BelongsToShop, HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'day' => 'date',
            'product_id' => 'integer',
            'total' => 'integer',
        ];
    }
}
