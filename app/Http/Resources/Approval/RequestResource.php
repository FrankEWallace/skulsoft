<?php

namespace App\Http\Resources\Approval;

use App\Enums\Approval\Status;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\Finance\LedgerResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $requester = $request->employees?->firstWhere('user_id', $this->request_user_id);

        $approver = $request->employees?->firstWhere('user_id', $this->actionable_user_id);

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'title' => $this->title,
            'type' => TypeResource::make($this->type),
            'priority' => OptionResource::make($this->priority),
            'group' => OptionResource::make($this->group),
            'nature' => OptionResource::make($this->nature),
            'amount' => $this->amount,
            'date' => $this->date,
            'due_date' => $this->due_date,
            'status' => Status::getDetail($this->status),
            $this->mergeWhen($this->type->category->value == 'item_based' && $this->type->getConfig('item_based_type') == 'item_with_quantity', [
                'items' => collect($this->items)->map(function ($item) {
                    return [
                        'uuid' => Arr::get($item, 'uuid'),
                        'item' => Arr::get($item, 'item'),
                        'quantity' => Arr::get($item, 'quantity'),
                        'unit' => Arr::get($item, 'unit'),
                        'price' => \Price::from(Arr::get($item, 'price', 0)),
                        'description' => Arr::get($item, 'description'),
                    ];
                }),
            ]),
            $this->mergeWhen($this->type->category->value == 'item_based' && $this->type->getConfig('item_based_type') == 'item_without_quantity', [
                'items' => collect($this->items)->map(function ($item) {
                    return [
                        'uuid' => Arr::get($item, 'uuid'),
                        'item' => Arr::get($item, 'item'),
                        'amount' => \Price::from(Arr::get($item, 'amount', 0)),
                        'description' => Arr::get($item, 'description'),
                    ];
                }),
            ]),
            $this->mergeWhen($this->type->category->value == 'contact_based', [
                'contact' => [
                    'name' => Arr::get($this->contact, 'name'),
                    'contact_number' => Arr::get($this->contact, 'contact_number'),
                    'email' => Arr::get($this->contact, 'email'),
                    'website' => Arr::get($this->contact, 'website'),
                    'tax_number' => Arr::get($this->contact, 'tax_number'),
                    'address' => Arr::get($this->contact, 'address'),
                    'address_display' => Arr::toAddress(Arr::get($this->contact, 'address')),
                ],
            ]),
            $this->mergeWhen($this->type->category->value == 'payment_based', [
                'vendor' => LedgerResource::make($this->vendor),
                'payment' => [
                    'payee' => Arr::get($this->payment, 'payee'),
                    'amount' => \Price::from(Arr::get($this->payment, 'amount', 0)),
                    'invoice_number' => Arr::get($this->payment, 'invoice_number'),
                    'invoice_date' => \Cal::date(Arr::get($this->payment, 'invoice_date')),
                    'mode' => Arr::get($this->payment, 'mode'),
                    'details' => Arr::get($this->payment, 'details'),
                ],
            ]),
            'is_actionable' => $this->isActionable(),
            $this->mergeWhen($request->show_details, [
                'records' => $this->getRecords($request),
            ]),
            'requester' => $requester ? EmployeeSummaryResource::make($requester) : null,
            'approver' => $approver ? EmployeeSummaryResource::make($approver) : null,
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'purpose' => $this->purpose,
            'description' => $this->description,
            'is_editable' => $this->is_editable,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getRecords($request)
    {
        if (! $this->relationLoaded('requestRecords')) {
            return [];
        }

        $approvalLevels = $this->type->levels;

        return $this->requestRecords->map(function ($record) use ($request) {
            $approver = $request->employees?->firstWhere('user_id', $record->user_id);

            $duration = null;
            if ($record->received_at->value && $record->processed_at->value) {
                $receivedAt = Carbon::parse($record->received_at->value);
                $processedAt = Carbon::parse($record->processed_at->value);
                $duration = $processedAt->diffForHumans($receivedAt);
            }

            return [
                'uuid' => $record->uuid,
                'employee' => $approver ? EmployeeSummaryResource::make($approver) : null,
                'is_actionable' => $approver ? $approver->user_id == auth()->id() : false,
                'comment' => $record->comment,
                'status' => Status::getDetail($record->status),
                'received_at' => $record->received_at,
                'processed_at' => $record->processed_at,
                'duration' => $duration,
                'is_other_team_member' => $approver->team_id != auth()->user()->current_team_id,
            ];
        });
    }
}
