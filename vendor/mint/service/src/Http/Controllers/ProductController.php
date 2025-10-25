<?php

namespace Mint\Service\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mint\Service\Services\ProductService;

class ProductController extends Controller
{
    /**
     * Get product information
     */
    public function info(ProductService $service)
    {
        return $service->getInfo();
    }

    /**
     * Confirm product information
     */
    public function confirm(ProductService $service)
    {
        return $service->confirm();
    }

    /**
     * Get product license
     */
    public function license(Request $request, ProductService $service)
    {
        $service->license($request);

        return response()->success(['message' => trans('setup.license.verified')]);
    }

    /**
     * Update product
     */
    public function update(Request $request, ProductService $service)
    {
        $service->update($request);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('setup.product')])]);
    }
}
