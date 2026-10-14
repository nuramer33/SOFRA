<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Manual ordering with a `sort_order` column ("move up / move down").
 *
 * The model defines which rows it is ordered among via sortableSiblings(),
 * e.g. categories among the shop's categories, products within a category.
 */
trait Sortable
{
    abstract protected function sortableSiblings(): Builder;

    /**
     * Give a new row the last position among its siblings.
     */
    public static function bootSortable(): void
    {
        static::creating(function (self $model) {
            if (! $model->isDirty('sort_order')) {
                $model->sort_order = (int) $model->sortableSiblings()->max('sort_order') + 1;
            }
        });
    }

    /**
     * @param  'up'|'down'  $direction
     */
    public function move(string $direction): void
    {
        DB::transaction(function () use ($direction) {
            $siblings = $this->sortableSiblings()
                ->orderBy('sort_order')->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->values();

            $from = $siblings->search(fn (self $sibling) => $sibling->is($this));
            $to = $direction === 'up' ? $from - 1 : $from + 1;

            if ($from === false || ! $siblings->has($to)) {
                return;
            }

            $ordered = $siblings->all();
            [$ordered[$from], $ordered[$to]] = [$ordered[$to], $ordered[$from]];

            // Renumber 0..n so duplicate or missing positions heal themselves.
            foreach ($ordered as $position => $sibling) {
                if ($sibling->sort_order !== $position) {
                    $sibling->sort_order = $position;
                    $sibling->save();
                }
            }
        });
    }
}
