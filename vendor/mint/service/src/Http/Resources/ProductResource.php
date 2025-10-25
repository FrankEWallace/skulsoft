<?php

namespace Mint\Service\Http\Resources;

use App\Helpers\SysHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $nextReleaseBuild = Arr::get($this, 'next_release_build');
        $nextReleaseVersion = Arr::get($this, 'next_release_version');

        $updateFileName = base_path($nextReleaseBuild . '-' . $nextReleaseVersion . '.zip');

        $isTestMode = SysHelper::isTestMode();
        $isDownloaded = ($nextReleaseBuild && \File::exists($updateFileName)) ? true : false;

        $dateOfSupportExpiry = Arr::get($this, 'date_of_support_expiry');
        $dateOfPurchase = Arr::get($this, 'date_of_purchase');

        return [
            'name'                   => Arr::get($this, 'name'),
            'current_version'        => Arr::get($this, 'current_version'),
            'latest_version'         => Arr::get($this, 'latest_version'),
            $this->mergeWhen($isTestMode, [
                'purchase_code' => config('app.mask'),
                'access_code' => config('app.mask'),
                'email' => config('app.mask'),
            ]),
            $this->mergeWhen(!$isTestMode, [
                'purchase_code' => Arr::get($this, 'purchase_code'),
                'access_code' => Arr::get($this, 'access_code'),
                'email' => Arr::get($this, 'email'),
            ]),
            'license_type'           => Arr::get($this, 'license_type'),
            'date_of_purchase'       => \Cal::date($dateOfPurchase),
            'date_of_support_expiry' => \Cal::date($dateOfSupportExpiry),
            'is_support_expired'     => ($dateOfSupportExpiry && $dateOfSupportExpiry < today()->toDateString()) ? true : false,
            'is_update_available'    => $nextReleaseBuild ? true : false,
            'is_downloaded'          => $isDownloaded,
            'next_release' => [
                'version' => Arr::get($this, 'next_release_version'),
                'build' => Arr::get($this, 'next_release_build'),
                'date' => Arr::get($this, 'next_release_date'),
            ]
        ];
    }
}
