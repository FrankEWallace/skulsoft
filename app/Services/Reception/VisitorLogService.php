<?php

namespace App\Services\Reception;

use App\Enums\OptionType;
use App\Enums\Reception\VisitorType;
use App\Helpers\CalHelper;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use App\Models\Reception\VisitorLog;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class VisitorLogService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.reception.visitor_log_number_prefix');
        $numberSuffix = config('config.reception.visitor_log_number_suffix');
        $digit = config('config.reception.visitor_log_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) VisitorLog::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request): array
    {
        $types = VisitorType::getOptions();

        $purposes = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::VISITING_PURPOSE->value)
            ->get());

        $currentDateTime = CalHelper::toDateTime(now()->toDateTimeString());

        return compact('types', 'purposes', 'currentDateTime');
    }

    public function create(Request $request): VisitorLog
    {
        \DB::beginTransaction();

        $visitorLog = VisitorLog::forceCreate($this->formatParams($request));

        $this->saveImages($request, $visitorLog);

        $visitorLog->addMedia($request);

        \DB::commit();

        return $visitorLog;
    }

    private function formatParams(Request $request, ?VisitorLog $visitorLog = null): array
    {
        $entryAt = CalHelper::storeDateTime($request->entry_at)?->toDateTimeString();
        $exitAt = $request->exit_at ? CalHelper::storeDateTime($request->exit_at)?->toDateTimeString() : null;

        $formatted = [
            'type' => $request->type,
            'purpose_id' => $request->purpose_id,
            'visitor_type' => $request->visitor_type,
            'visitor_id' => $request->visitor_id,
            'employee_id' => $request->employee_id,
            'entry_at' => $entryAt,
            'exit_at' => $exitAt,
            'count' => $request->count,
            'remarks' => $request->remarks,
        ];

        if ($request->type == 'other') {
            $formatted['name'] = $request->name;
            $formatted['company']['name'] = $request->company_name;
            $formatted['contact_number'] = $request->contact_number;
        }

        if (! $visitorLog) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    private function saveImages(Request $request, VisitorLog $visitorLog)
    {
        $images = [];
        foreach ($request->images as $image) {
            if (Arr::get($image, 'path')) {
                $images[] = Arr::get($image, 'path');

                continue;
            }

            if (empty(Arr::get($image, 'image'))) {
                continue;
            }

            $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', Arr::get($image, 'image'));

            $binaryImage = base64_decode($base64Data);

            $storagePath = 'visitor-log/';
            $filename = uniqid('image_').'.jpg';
            $images[] = $storagePath.$filename;

            \Storage::disk('public')->put($storagePath.$filename, $binaryImage);
        }

        $visitorLog->setMeta([
            'images' => $images,
        ]);
        $visitorLog->save();
    }

    public function update(Request $request, VisitorLog $visitorLog): void
    {
        \DB::beginTransaction();

        $visitorLog->forceFill($this->formatParams($request, $visitorLog))->save();

        $this->saveImages($request, $visitorLog);

        $visitorLog->updateMedia($request);

        \DB::commit();
    }

    public function deletable(VisitorLog $visitorLog): void
    {
        //
    }
}
