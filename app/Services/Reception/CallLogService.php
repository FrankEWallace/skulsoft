<?php

namespace App\Services\Reception;

use App\Enums\OptionType;
use App\Enums\Reception\CallType;
use App\Helpers\CalHelper;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use App\Models\Reception\CallLog;
use Illuminate\Http\Request;

class CallLogService
{
    public function preRequisite(Request $request): array
    {
        $types = CallType::getOptions();

        $purposes = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::CALLING_PURPOSE->value)
            ->get());

        return compact('types', 'purposes');
    }

    public function create(Request $request): CallLog
    {
        \DB::beginTransaction();

        $callLog = CallLog::forceCreate($this->formatParams($request));

        $callLog->addMedia($request);

        \DB::commit();

        return $callLog;
    }

    private function formatParams(Request $request, ?CallLog $callLog = null): array
    {
        $formatted = [
            'type' => $request->type,
            'purpose_id' => $request->purpose_id,
            'call_at' => CalHelper::storeDateTime($request->call_at)->toDateTimeString(),
            'name' => $request->name,
            'company' => [
                'name' => $request->company_name,
            ],
            'incoming_number' => $request->incoming_number,
            'outgoing_number' => $request->outgoing_number,
            'duration' => $request->duration,
            'conversation' => $request->conversation,
            'remarks' => $request->remarks,
        ];

        if (! $callLog) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
            $formatted['user_id'] = auth()->id();
        }

        return $formatted;
    }

    public function update(Request $request, CallLog $callLog): void
    {
        \DB::beginTransaction();

        $callLog->forceFill($this->formatParams($request, $callLog))->save();

        $callLog->updateMedia($request);

        \DB::commit();
    }

    public function deletable(CallLog $callLog): void
    {
        //
    }
}
