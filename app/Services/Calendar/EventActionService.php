<?php

namespace App\Services\Calendar;

use App\Concerns\HasStorage;
use App\Models\Calendar\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EventActionService
{
    use HasStorage;

    public function uploadAsset(Request $request, Event $event, string $type)
    {
        request()->validate([
            'image' => 'required|image',
        ]);

        $assets = $event->getMeta('assets', []);
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'calendar/event/assets/'.$type,
            input: 'image',
            url: false
        );

        $assets[$type] = $image;
        $event->setMeta(['assets' => $assets]);
        $event->save();
    }

    public function removeAsset(Request $request, Event $event, string $type)
    {
        $assets = $event->getMeta('assets', []);
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        unset($assets[$type]);
        $event->setMeta(['assets' => $assets]);
        $event->save();
    }

    public function pin(Request $request, Event $event): void
    {
        if ($event->getMeta('pinned_at')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $event->setMeta([
            'pinned_at' => now()->toDateTimeString(),
        ]);
        $event->save();
    }

    public function unpin(Request $request, Event $event): void
    {
        if (empty($event->getMeta('pinned_at'))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $event->setMeta([
            'pinned_at' => null,
        ]);
        $event->save();
    }
}
