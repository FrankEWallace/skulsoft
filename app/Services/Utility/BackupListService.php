<?php

namespace App\Services\Utility;

use App\Concerns\CollectionPaginator;
use App\Contracts\ListGenerator;
use App\Helpers\SysHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BackupListService extends ListGenerator
{
    use CollectionPaginator;

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('utility.backup.props.name'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'size',
                'label' => trans('utility.backup.props.size'),
                'sortable' => false,
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
        $items = [];

        $disk = Arr::first(config('backup.backup.destination.disks', []));

        foreach (\Storage::disk($disk)->files('backup') as $filename) {
            $items[] = [
                'name' => basename($filename),
                'size' => SysHelper::filesize(\Storage::disk($disk)->size($filename)),
            ];
        }

        $items = $this->collectionPaginate($items, $this->getPageLength(), $this->getCurrentPage());

        $items['headers'] = $this->getHeaders();

        return $items;
    }

    public function list(Request $request): array
    {
        return $this->paginate($request);
    }
}
