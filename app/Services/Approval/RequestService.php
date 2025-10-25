<?php

namespace App\Services\Approval;

use App\Enums\Approval\Category;
use App\Enums\Approval\Status as ApprovalRequestStatus;
use App\Enums\OptionType;
use App\Http\Resources\Approval\TypeResource;
use App\Http\Resources\OptionResource;
use App\Models\Activity;
use App\Models\Approval\Request as ApprovalRequest;
use App\Models\Approval\Type as ApprovalType;
use App\Models\Option;
use App\Models\RequestRecord;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class RequestService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.approval.request_number_prefix');
        $numberSuffix = config('config.approval.request_number_suffix');
        $digit = config('config.approval.request_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) ApprovalRequest::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request): array
    {
        $types = TypeResource::collection(ApprovalType::query()
            ->with('department')
            ->where('config->is_active', true)
            ->byTeam()
            ->get());

        $priorities = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::APPROVAL_REQUEST_PRIORITY->value)
            ->get());

        $groups = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::APPROVAL_REQUEST_GROUP->value)
            ->get());

        $natures = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::APPROVAL_REQUEST_NATURE->value)
            ->get());

        $categories = Category::getOptions();

        return compact('types', 'categories', 'priorities', 'groups', 'natures');
    }

    public function create(Request $request): ApprovalRequest
    {
        \DB::beginTransaction();

        $approvalRequest = ApprovalRequest::forceCreate($this->formatParams($request));

        $this->updateRequestRecords($request, $approvalRequest);

        $approvalRequest->addMedia($request);

        Activity::forceCreate([
            'description' => [
                'activity' => 'approval.request.activity.created',
                'attributes' => [
                    'title' => $request->title,
                    'code_number' => $approvalRequest->code_number,
                ],
            ],
            'event' => 'created',
            'subject_type' => 'ApprovalRequest',
            'subject_id' => $approvalRequest->id,
            'user_id' => auth()->id(),
            'properties' => [],
        ]);

        \DB::commit();

        return $approvalRequest;
    }

    private function formatParams(Request $request, ?ApprovalRequest $approvalRequest = null): array
    {
        $formatted = [
            'title' => $request->title,
            'priority_id' => $request->priority_id,
            'group_id' => $request->group_id,
            'nature_id' => $request->nature_id,
            'vendor_id' => $request->vendor_id,
            'due_date' => $request->due_date,
            'description' => $request->description,
        ];

        $type = $request->type;

        if ($type->category->value == 'item_based' && $type->getConfig('item_based_type') == 'item_with_quantity') {
            $formatted['items'] = collect($request->items)->map(function ($item) {
                return [
                    'uuid' => Arr::get($item, 'uuid'),
                    'item' => Arr::get($item, 'item'),
                    'quantity' => Arr::get($item, 'quantity'),
                    'unit' => Arr::get($item, 'unit'),
                    'price' => Arr::get($item, 'price'),
                    'description' => Arr::get($item, 'description'),
                ];
            });
            $formatted['amount'] = collect($request->items)->sum(function ($item) {
                return Arr::get($item, 'quantity', 1) * Arr::get($item, 'price', 0);
            });
        } elseif ($type->category->value == 'item_based' && $type->getConfig('item_based_type') == 'item_without_quantity') {
            $formatted['items'] = collect($request->items)->map(function ($item) {
                return [
                    'uuid' => Arr::get($item, 'uuid'),
                    'item' => Arr::get($item, 'item'),
                    'amount' => Arr::get($item, 'amount'),
                    'description' => Arr::get($item, 'description'),
                ];
            });
            $formatted['amount'] = collect($request->items)->sum(function ($item) {
                return Arr::get($item, 'amount', 0);
            });
        } elseif ($type->category->value == 'payment_based') {
            $formatted['vendor_id'] = $request->vendor_id;
            $formatted['payment'] = $request->payment;
            $formatted['amount'] = $request->input('payment.amount', 0);
        } elseif ($type->category->value == 'contact_based') {
            $formatted['contact'] = $request->contact;
        } elseif ($type->category->value == 'other') {
            $formatted['purpose'] = $request->purpose;
        }

        if (! $approvalRequest) {
            $formatted['type_id'] = $request->type_id;
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');

            $formatted['date'] = today()->toDateString();
            $formatted['status'] = ApprovalRequestStatus::REQUESTED->value;
            $formatted['request_user_id'] = auth()->id();
        }

        return $formatted;
    }

    private function updateRequestRecords(Request $request, ApprovalRequest $approvalRequest): void
    {
        foreach ($request->levels as $index => $level) {
            $requestRecord = RequestRecord::firstOrCreate([
                'model_type' => 'ApprovalRequest',
                'model_id' => $approvalRequest->id,
                'user_id' => Arr::get($level, 'user_id'),
            ]);

            if ($index == 0 && ! $requestRecord->received_at->value) {
                $requestRecord->received_at = now()->toDateTimeString();
                $requestRecord->processed_at = null;
            }

            $requestRecord->status = ApprovalRequestStatus::REQUESTED->value;
            $requestRecord->save();
        }
    }

    private function isEditable(ApprovalRequest $approvalRequest): bool
    {
        if (! $approvalRequest->is_editable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        return true;
    }

    public function update(Request $request, ApprovalRequest $approvalRequest): void
    {
        $this->isEditable($approvalRequest);

        if ($approvalRequest->request_user_id != auth()->id()) {
            $allowedActions = $approvalRequest->getAllowedActions();

            if (! in_array('edit', $allowedActions)) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }
        }

        \DB::beginTransaction();

        $approvalRequest->forceFill($this->formatParams($request, $approvalRequest))->save();

        if ($approvalRequest->request_user_id == auth()->id()) {
            $this->updateRequestRecords($request, $approvalRequest);

            if ($approvalRequest->status == ApprovalRequestStatus::RETURNED->value) {
                $approvalRequest->setMeta([
                    'return_to_requester' => false,
                ]);
                $approvalRequest->status = ApprovalRequestStatus::REQUESTED->value;
                $approvalRequest->save();
            }
        }

        $approvalRequest->updateMedia($request);

        Activity::forceCreate([
            'description' => [
                'activity' => 'approval.request.activity.updated',
                'attributes' => [
                    'title' => $request->title,
                    'code_number' => $approvalRequest->code_number,
                ],
            ],
            'event' => 'updated',
            'subject_type' => 'ApprovalRequest',
            'subject_id' => $approvalRequest->id,
            'user_id' => auth()->id(),
            'properties' => [],
        ]);

        \DB::commit();
    }

    public function deletable(ApprovalRequest $approvalRequest): void
    {
        $this->isEditable($approvalRequest);
    }

    public function delete(ApprovalRequest $approvalRequest): void
    {
        \DB::beginTransaction();

        Activity::forceCreate([
            'description' => [
                'activity' => 'approval.request.activity.deleted',
                'attributes' => [
                    'title' => $approvalRequest->title,
                    'code_number' => $approvalRequest->code_number,
                ],
            ],
            'event' => 'deleted',
            'subject_type' => 'ApprovalRequest',
            'subject_id' => $approvalRequest->id,
            'user_id' => auth()->id(),
            'properties' => [],
        ]);

        $approvalRequest->delete();

        \DB::commit();
    }
}
