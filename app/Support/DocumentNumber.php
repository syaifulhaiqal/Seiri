<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Next number in a padded series such as INV-0001 or JE-000001.
 */
class DocumentNumber
{
    /**
     * @param  Builder<Model>  $query  rows that share the series
     */
    public static function next(Builder $query, string $prefix, int $pad = 4, string $column = 'number'): string
    {
        $last = (clone $query)
            ->where($column, 'like', $prefix . '%')
            ->orderByRaw('length(' . $column . ') desc')
            ->orderByDesc($column)
            ->value($column);

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }
}
