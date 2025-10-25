<?php

namespace App\Services\Activity;

use App\Concerns\HasStorage;
use App\Models\Activity\Trip;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TripActionService
{
    use HasStorage;

    public function uploadAsset(Request $request, Trip $trip, string $type)
    {
        request()->validate([
            'image' => 'required|image',
        ]);

        $assets = $trip->getMeta('assets', []);
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'activity/trip/assets/'.$type,
            input: 'image',
            url: false
        );

        $assets[$type] = $image;
        $trip->setMeta(['assets' => $assets]);
        $trip->save();
    }

    public function removeAsset(Request $request, Trip $trip, string $type)
    {
        $assets = $trip->getMeta('assets', []);
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        unset($assets[$type]);
        $trip->setMeta(['assets' => $assets]);
        $trip->save();
    }

    public function uploadMedia(Request $request, Trip $trip)
    {
        $trip->updateMedia($request);
    }

    public function removeMedia(Trip $trip, string $uuid)
    {
        $media = Media::query()
            ->whereModelType($trip->getModelName())
            ->where('status', 1)
            ->whereModelId($trip->id)
            ->whereUuid($uuid)
            ->getOrFail(trans('general.file'));

        if (\Storage::exists($media->name)) {
            \Storage::delete($media->name);
        }

        $media->delete();
    }
}
