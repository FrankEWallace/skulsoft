<?php

namespace App\Services\Reception;

use App\Enums\OptionType;
use App\Enums\Reception\EnquiryStatus;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use App\Models\Reception\Enquiry;
use App\Models\Reception\EnquiryFollowUp;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnquiryFollowUpService
{
    public function preRequisite(Request $request): array
    {
        $statuses = EnquiryStatus::getOptions();

        $stages = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::ENQUIRY_STAGE->value)
            ->get());

        return compact('statuses', 'stages');
    }

    public function findByUuidOrFail(Enquiry $enquiry, string $followUp): EnquiryFollowUp
    {
        return EnquiryFollowUp::query()
            ->whereEnquiryId($enquiry->id)
            ->whereUuid($followUp)
            ->getOrFail(trans('reception.enquiry.follow_up.follow_up'));
    }

    public function create(Request $request, Enquiry $enquiry): EnquiryFollowUp
    {
        \DB::beginTransaction();

        $followUp = EnquiryFollowUp::forceCreate($this->formatParams($request, $enquiry));

        $enquiry->status = $request->status;
        $enquiry->stage_id = $request->stage_id;
        $enquiry->save();

        \DB::commit();

        return $followUp;
    }

    private function formatParams(Request $request, Enquiry $enquiry, ?EnquiryFollowUp $followUp = null): array
    {
        if ($request->follow_up_date < $enquiry->date->value) {
            throw ValidationException::withMessages(['message' => trans('reception.enquiry.follow_up.could_not_follow_up_before_enquiry_date')]);
        }

        $previousFollowUpDate = EnquiryFollowUp::query()
            ->whereEnquiryId($enquiry->id)
            ->orderBy('follow_up_date', 'desc')
            ->first();

        if ($previousFollowUpDate && $request->follow_up_date < $previousFollowUpDate->follow_up_date->value) {
            throw ValidationException::withMessages(['message' => trans('reception.enquiry.follow_up.could_not_follow_up_before_previous_follow_up_date')]);
        }

        $formatted = [
            'enquiry_id' => $enquiry->id,
            'follow_up_date' => $request->follow_up_date,
            'next_follow_up_date' => $request->next_follow_up_date,
            'status' => $request->status,
            'stage_id' => $request->stage_id,
            'remarks' => $request->remarks,
        ];

        if (! $followUp) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Enquiry $enquiry, EnquiryFollowUp $followUp): void
    {
        $this->isEditable($enquiry, $followUp);

        \DB::beginTransaction();

        $followUp->forceFill($this->formatParams($request, $enquiry, $followUp))->save();

        $enquiry->status = $request->status;
        $enquiry->stage_id = $request->stage_id;
        $enquiry->save();

        \DB::commit();
    }

    public function updateEnquiryStatus(Enquiry $enquiry): void
    {
        $lastFollowUp = EnquiryFollowUp::query()
            ->whereEnquiryId($enquiry->id)
            ->orderBy('follow_up_date', 'desc')
            ->first();

        $enquiry->status = $lastFollowUp?->status ?? EnquiryStatus::OPEN;
        $enquiry->stage_id = $lastFollowUp?->stage_id ?? null;
        $enquiry->save();
    }

    private function isEditable(Enquiry $enquiry, EnquiryFollowUp $followUp): bool
    {
        $enquiryFollowUps = EnquiryFollowUp::query()
            ->whereEnquiryId($enquiry->id)
            ->where('follow_up_date', '>', $followUp->follow_up_date->value)
            ->get();

        if ($enquiryFollowUps->count()) {
            throw ValidationException::withMessages(['message' => trans('reception.enquiry.could_not_modify_if_not_last_follow_up')]);
        }

        return true;
    }

    public function deletable(Enquiry $enquiry, EnquiryFollowUp $followUp): void
    {
        $this->isEditable($enquiry, $followUp);

        // if ($enquiry->status != EnquiryStatus::OPEN) {
        //     throw ValidationException::withMessages(['message' => trans('reception.enquiry.could_not_delete_if_closed')]);
        // }
    }
}
