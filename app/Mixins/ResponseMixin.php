<?php

namespace App\Mixins;

use Illuminate\Contracts\Support\Arrayable;

class ResponseMixin
{
    /**
     * Return ok response
     *
     * @param  mixed  $items
     */
    public function ok()
    {
        return function ($items = null, $status = 200) {
            return response()->json($items, $status);
        };
    }

    /**
     * Return success response
     *
     * @param  mixed  $items
     */
    public function success()
    {
        return function ($items = null, $status = 200) {
            $data = [];

            if ($items instanceof Arrayable) {
                $items = $items->toArray();
            }

            if (is_array($items)) {
                $data = [
                    'status' => 'success',
                    ...$items,
                ];
            }

            return response()->json($data, $status);
        };
    }

    /**
     * Return error response
     *
     * @param  mixed  $items
     */
    public function error()
    {
        return function ($items = null, $code = null, $status = 422) {
            $data = ['status' => 'error'];

            if (is_array($items)) {
                $data['errors'] = [
                    ...$items,
                ];
            }

            if ($code) {
                $data['code'] = $code;
            }

            return response()->json($data, $status);
        };
    }
}
