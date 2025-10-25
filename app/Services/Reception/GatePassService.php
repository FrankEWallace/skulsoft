<?php

namespace App\Services\Reception;

use App\Enums\OptionType;
use App\Enums\Reception\GatePassStatus;
use App\Enums\Reception\GatePassTo;
use App\Helpers\CalHelper;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use App\Models\Reception\GatePass;
use App\Support\FormatCodeNumber;
use App\Support\HasAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class GatePassService
{
    use FormatCodeNumber, HasAudience;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.reception.gate_pass_number_prefix');
        $numberSuffix = config('config.reception.gate_pass_number_suffix');
        $digit = config('config.reception.gate_pass_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) GatePass::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request): array
    {
        $to = GatePassTo::getOptions();

        $purposes = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::GATE_PASS_PURPOSE->value)
            ->get());

        $currentDateTime = CalHelper::toDateTime(now()->toDateTimeString());

        return compact('to', 'purposes', 'currentDateTime');
    }

    public function create(Request $request): GatePass
    {
        \DB::beginTransaction();

        $gatePass = GatePass::forceCreate($this->formatParams($request));

        $this->storeAudience($gatePass, $request->all());

        $this->saveImages($request, $gatePass);

        $gatePass->addMedia($request);

        \DB::commit();

        return $gatePass;
    }

    private function formatParams(Request $request, ?GatePass $gatePass = null): array
    {
        $formatted = [
            'start_at' => CalHelper::storeDateTime($request->start_at)->toDateTimeString(),
            'requester_type' => $request->requester_type,
            'purpose_id' => $request->purpose_id,
            'reason' => $request->reason,
            'remarks' => $request->remarks,
        ];

        if (! $gatePass) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
            $formatted['status'] = GatePassStatus::PENDING;
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    private function saveImages(Request $request, GatePass $gatePass)
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

            $storagePath = 'gate-pass/';
            $filename = uniqid('image_').'.jpg';
            $images[] = $storagePath.$filename;

            \Storage::disk('public')->put($storagePath.$filename, $binaryImage);
        }

        $gatePass->setMeta([
            'images' => $images,
        ]);
        $gatePass->save();
    }

    public function update(Request $request, GatePass $gatePass): void
    {
        \DB::beginTransaction();

        $gatePass->forceFill($this->formatParams($request, $gatePass))->save();

        $this->updateAudience($gatePass, $request->all());

        $this->saveImages($request, $gatePass);

        $gatePass->updateMedia($request);

        \DB::commit();
    }

    public function deletable(GatePass $gatePass): void
    {
        //
    }
}
