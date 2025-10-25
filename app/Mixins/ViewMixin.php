<?php

namespace App\Mixins;

use App\Exports\ListExport;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;

class ViewMixin
{
    /**
     * Export items
     *
     * @param  array  $rows
     * @param  string  $filename
     * @param  bool  $footer
     */
    public function export()
    {
        return function ($rows = [], string $filename = 'Download', bool $footer = false, array $params = []) {
            $meta = Arr::get($rows, 'meta', []);
            unset($rows['meta']);

            $footer = Arr::get($meta, 'has_footer', $footer);

            $filename = Arr::get($meta, 'filename', $filename);

            $output = request()->query('output');

            if ($output == 'excel' || $output == 'export_all_excel') {
                return Excel::download(new ListExport($rows), $filename.'.xlsx');
            }

            $headers = Arr::first($rows);
            array_shift($rows);

            $footers = [];
            if ($footer) {
                $footers = Arr::last($rows);
                array_pop($rows);
            }

            $view = 'print.index';

            if (Arr::get($params, 'custom_layout') && view()->exists('print.'.$params['custom_layout'])) {
                $view = 'print.'.$params['custom_layout'];
            }

            if (view()->exists('print.custom')) {
                $view = 'print.custom';
            }

            $customPrint = Arr::get($meta, 'print');
            if (view()->exists(config('config.print.custom_path').$customPrint)) {
                $view = config('config.print.custom_path').$customPrint;
            }

            if ($output == 'pdf') {
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'orientation' => Arr::get($meta, 'orientation', 'portrait') == 'portrait' ? 'P' : 'L',
                ]);
                // to support unicode characters
                $mpdf->autoScriptToLang = true;
                $mpdf->autoLangToFont = true;
                $mpdf->WriteHTML(view($view, compact('headers', 'meta', 'rows', 'footer', 'footers'))->render());
                $mpdf->Output();
                // $pdf = \PDF::loadView($view, compact('headers', 'rows', 'footer', 'footers'));
                // return $pdf->stream($filename . '.pdf');
            }

            return view($view, compact('headers', 'meta', 'rows', 'footer', 'footers'));
        };
    }
}
