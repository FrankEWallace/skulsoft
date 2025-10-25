<?php

namespace App\Concerns;

use Illuminate\Support\Arr;

trait HasPagination
{
    public static function getSort($allowedSorts = ['created_at'], $defaultSort = 'created_at'): string
    {
        $sort = request()->query('sort');

        return in_array($sort, $allowedSorts) ? $sort : $defaultSort;
    }

    public static function getOrder($defaultOrder = 'desc'): string
    {
        $order = request()->query('order', 'desc');

        return in_array($order, ['asc', 'desc']) ? $order : $defaultOrder;
    }

    public static function getPageLength(): int
    {
        $perPage = (int) request()->query('per_page', config('config.system.per_page'));

        $lists = Arr::getVar('list');
        $paginations = Arr::get($lists, 'config.paginations', []);

        if (! in_array($perPage, $paginations)) {
            $perPage = config('config.system.per_page');
        }

        return $perPage;
    }

    public static function getCurrentPage(): int
    {
        $currentPage = request()->query('current_page', 1);

        if (! is_int($currentPage)) {
            $currentPage = 1;
        }

        if ($currentPage <= 0) {
            $currentPage = 1;
        }

        return $currentPage;
    }
}
