<?php

namespace App\Contracts;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class ListGenerator extends PaginationHelper
{
    protected $actionHeader = [
        'key' => 'action',
        'label' => '',
        'sortable' => false,
        'visibility' => true,
    ];

    public function export($list): mixed
    {
        $items = $this->listArray($list);

        $data = $this->getAdditionalData($list);

        $rows = $this->prepareForExport($data, $items);

        return view()->export(rows: $rows, params: [
            'custom_layout' => Arr::get($data, 'meta.custom_layout'),
        ]);
    }

    public function listArray($items): array
    {
        if (is_array($items)) {
            return array_key_exists('data', $items) ? $items['data'] : $items;
        }

        return json_decode($items->toJson(), true);
    }

    public function getAdditionalData($items): array
    {
        if ($items instanceof ResourceCollection) {
            return $items->additional;
        }

        if (Arr::get($items, 'headers')) {
            return [
                'headers' => Arr::get($items, 'headers'),
            ];
        }

        return [];
    }

    public function prepareForExport($data, $items, $footers = []): array
    {
        $headers = Arr::get($data, 'headers', []);
        $meta = Arr::get($data, 'meta', []);

        $sno = Arr::get($meta, 'sno');

        if ($sno && Arr::get(Arr::first($headers), 'key') != 'sno') {
            array_unshift($headers, [
                'key' => 'sno',
                'label' => trans('general.sno'),
                'sortable' => false,
                'visibility' => true,
            ]);
        }

        if (empty($footers)) {
            $footers = Arr::get($data, 'footers', []);
        }

        $output = request()->query('output');

        if ($output == 'export_all_excel') {
            $output = 'excel';
        }

        if (request()->query('columns')) {
            $keys = explode(',', request()->query('columns'));

            if ($sno) {
                array_unshift($keys, 'sno');
            }

            $headers = Arr::where($headers, function ($header) use ($keys) {
                return Arr::get($header, 'printable', true) && in_array(Arr::get($header, 'key'), $keys);
            });
        }

        if ($output == 'excel') {
            $headers = Arr::where($headers, function ($header) {
                return Arr::get($header, 'label');
            });
        }

        $rows[] = Arr::pluck($headers, 'label');
        $keys = Arr::pluck($headers, 'key');

        foreach ($items as $item) {
            $row = [];
            foreach ($keys as $key) {
                $header = Arr::first($headers, function ($header) use ($key) {
                    return Arr::get($header, 'key') == $key;
                });

                $printLabel = Arr::get($header, 'print_label');
                $printSubLabel = Arr::get($header, 'print_sub_label');
                $printAdditionalLabel = Arr::get($header, 'print_additional_label');
                $type = Arr::get($header, 'type');

                if ($key == 'sno') {
                    $data = $sno++;
                } elseif (empty($printLabel)) {
                    $data = Arr::get($item, snake_case($key));
                } elseif (Str::contains($printLabel, ' + ')) {
                    $concatenatedPrintLabel = explode(' + ', $printLabel);
                    $data = '';
                    foreach ($concatenatedPrintLabel as $label) {
                        $data .= Arr::get($item, snake_case($label)).' ';
                    }
                } elseif (! Str::contains($printLabel, ' + ')) {
                    $data = Arr::get($item, $printLabel);
                }

                if ($type == 'currency' && $output == 'excel') {
                    $data = preg_replace('/[^0-9]/', '', $data);
                }

                if ($printSubLabel) {
                    if ($output != 'excel') {
                        $subLabel = Arr::get($item, $printSubLabel);
                        if ($printAdditionalLabel && Arr::get($item, $printAdditionalLabel)) {
                            $subLabel .= ' - '.Arr::get($item, $printAdditionalLabel);
                        }
                        $data = [
                            'label' => Arr::get($item, $printLabel),
                            'sub_label' => $subLabel,
                        ];
                    } else {
                        $data = Arr::get($item, $printLabel);
                        if (empty($data)) {
                            $data = Arr::get($item, $printSubLabel);
                        } elseif ($data && Arr::get($item, $printSubLabel)) {
                            $data .= ' - '.Arr::get($item, $printSubLabel);
                        }
                        if ($type == 'currency' && $output == 'excel') {
                            $data = preg_replace('/[^0-9]/', '', $data);
                        }
                    }
                }

                if (is_bool($data)) {
                    $data = $data ? trans('general.yes') : trans('general.no');
                    // $data = $data ? '✓' : '✕';
                }

                if ($type === 'array' && is_array($data)) {
                    $arrayRow = [];
                    foreach ($data as $dataItem) {
                        $printKeys = explode(',', Arr::get($header, 'print_key'));
                        $titles = [];
                        foreach ($printKeys as $printKey) {
                            $titles[] = Arr::get($dataItem, $printKey);
                        }

                        $title = implode(' ', $titles);

                        $subTitle = '';
                        if (array_key_exists('print_sub_key', $header)) {
                            $printSubKeys = explode(',', Arr::get($header, 'print_sub_key'));
                            $subTitles = [];
                            foreach ($printSubKeys as $printSubKey) {
                                $subTitles[] = Arr::get($dataItem, $printSubKey);
                            }

                            $subTitle = implode(' ', $subTitles);
                        }
                        $arrayRow[] = $subTitle ? ($title.' - '.$subTitle) : $title;
                    }

                    // $data = implode(', ', $arrayRow);
                    $data = $arrayRow;
                }
                array_push($row, $data);
            }
            $rows[] = $row;
        }

        if (count($footers)) {
            $row = [];
            foreach ($keys as $key) {
                $header = Arr::first($headers, function ($header) use ($key) {
                    return Arr::get($header, 'key') == $key;
                });

                $type = Arr::get($header, 'type');

                $footer = Arr::first($footers, function ($item) use ($key) {
                    return Arr::get($item, 'key') == $key;
                });

                $label = Arr::get($footer, 'label');
                if ($type == 'currency' && $output == 'excel') {
                    $label = preg_replace('/[^0-9]/', '', $label);
                }
                array_push($row, $label);
            }
            $rows[] = $row;
        }

        $rows['meta'] = $meta;

        return $rows;
    }
}
