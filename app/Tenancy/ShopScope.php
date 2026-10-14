<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Adds `WHERE shop_id = <current shop>` to every query on a tenant model,
 * whenever a current shop is known. This is what makes /products/5 return
 * 404 instead of another shop's product.
 */
class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = app(CurrentShop::class);

        if ($tenant->check()) {
            $builder->where($model->qualifyColumn('shop_id'), $tenant->id());
        }
    }
}
