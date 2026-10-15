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
     * Drag-and-drop ordering: put `$ids` first, in that order, then number
     * 0..n. Ids outside the scope (another shop's, say) are ignored and rows
     * not listed keep their current relative order at the end.
     *
     * @param  list<int>  $ids
     * @param  Builder<static>|null  $siblings  The rows being ordered; defaults to every row the current shop can see.
     */
    public static function arrange(array $ids, ?Builder $siblings = null): void
    {
        DB::transaction(function () use ($ids, $siblings) {
            $rows = ($siblings ?? static::query())
                ->orderBy('sort_order')->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $ordered = collect($ids)->unique()->filter(fn ($id) => $rows->has($id))
                ->concat($rows->keys()->diff($ids))
                ->values();

            foreach ($ordered as $position => $id) {
                $row = $rows[$id];

                if ($row->sort_order !== $position) {
                    $row->sort_order = $position;
                    $row->save();
                }
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
