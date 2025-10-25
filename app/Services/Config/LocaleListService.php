<?php

namespace App\Services\Config;

use App\Concerns\CollectionPaginator;
use App\Concerns\LocalStorage;
use App\Contracts\ListGenerator;
use Illuminate\Http\Request;

class LocaleListService extends ListGenerator
{
    use CollectionPaginator, LocalStorage;

    protected $storage_key = 'locales';

    protected $allowedSorts = ['name', 'code'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('config.locale.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'code',
                'label' => trans('config.locale.props.code'),
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function paginate(Request $request): array
    {
        $locales = collect($this->getLocales());

        if ($request->query('search')) {
            $locales = $locales->filter(function ($item) use ($request) {
                return $item['name'] === $request->query('search') || $item['code'] === $request->query('search');
            });
        }

        if ($this->getOrder() === 'asc') {
            $locales->sortBy($this->getSort());
        } else {
            $locales->sortByDesc($this->getSort());
        }

        $items = $this->collectionPaginate($locales->toArray(), $this->getPageLength(), $this->getCurrentPage());

        $items['headers'] = $this->getHeaders();

        return $items;
    }

    public function getLocales(): array
    {
        return $this->getKey($this->storage_key) ?? [];
    }
}
