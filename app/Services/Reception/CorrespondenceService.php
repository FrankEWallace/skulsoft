<?php

namespace App\Services\Reception;

use App\Enums\Reception\CorrespondenceMode;
use App\Enums\Reception\CorrespondenceType;
use App\Models\Reception\Correspondence;
use Illuminate\Http\Request;

class CorrespondenceService
{
    public function preRequisite(Request $request): array
    {
        $types = CorrespondenceType::getOptions();

        $modes = CorrespondenceMode::getOptions();

        return compact('types', 'modes');
    }

    public function create(Request $request): Correspondence
    {
        \DB::beginTransaction();

        $correspondence = Correspondence::forceCreate($this->formatParams($request));

        $correspondence->addMedia($request);

        \DB::commit();

        return $correspondence;
    }

    private function formatParams(Request $request, ?Correspondence $correspondence = null): array
    {
        $formatted = [
            'type' => $request->type,
            'mode' => $request->mode,
            'date' => $request->date,
            'reference_id' => $request->reference_id,
            'letter_number' => $request->letter_number,
            'sender' => [
                'title' => $request->sender_title,
                'address' => $request->sender_address,
            ],
            'receiver' => [
                'title' => $request->receiver_title,
                'address' => $request->receiver_address,
            ],
            'remarks' => $request->remarks,
        ];

        if (! $correspondence) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
            $formatted['user_id'] = auth()->id();
        }

        return $formatted;
    }

    public function update(Request $request, Correspondence $correspondence): void
    {
        \DB::beginTransaction();

        $correspondence->forceFill($this->formatParams($request, $correspondence))->save();

        $correspondence->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Correspondence $correspondence): void
    {
        //
    }
}
