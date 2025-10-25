<?php

namespace App\Contracts;

use Illuminate\Support\Arr;

abstract class PaginationHelper
{
    const DEFAULT_CARD_LENGTH = 12;

    const DEFAULT_BOARD_LENGTH = 50;

    protected $allowedSorts = ['created_at'];

    protected $defaultSort = 'created_at';

    protected $defaultOrder = 'desc';

    public function getSort(): string
    {
        $view = request()->query('view');

        if ($view == 'board') {
            return 'position';
        }

        $sort = snake_case(request()->query('sort'));

        return in_array($sort, $this->allowedSorts) ? $sort : $this->defaultSort;
    }

    public function getOrder(): string
    {
        $view = request()->query('view');

        if ($view == 'board') {
            return 'asc';
        }

        $order = request()->query('order', $this->defaultOrder);

        return in_array($order, ['asc', 'desc']) ? $order : $this->defaultOrder;
    }

    public function getPageLength(): int
    {
        $view = request()->query('view');

        if ($view == 'card') {
            return self::DEFAULT_CARD_LENGTH;
        }

        if ($view == 'board') {
            return self::DEFAULT_BOARD_LENGTH;
        }

        $perPage = (int) request()->query('per_page', config('config.system.per_page'));

        $lists = Arr::getVar('list');
        $paginations = Arr::get($lists, 'per_page_lengths', []);

        if (! in_array($perPage, $paginations)) {
            $perPage = config('config.system.per_page');
        }

        return $perPage;
    }

    public function getCurrentPage(): int
    {
        $currentPage = request()->query('current_page', 1);

        if (! is_numeric($currentPage)) {
            $currentPage = 1;
        }

        if ($currentPage <= 0) {
            $currentPage = 1;
        }

        return round($currentPage);
    }

    public function getSno(): int
    {
        return (int) ($this->getPageLength() * ((int) $this->getCurrentPage() - 1) + 1);
    }
}
