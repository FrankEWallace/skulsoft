<?php

namespace App\Http\Resources\Approval;

use App\Enums\Approval\Category;
use App\Http\Resources\Employee\DepartmentResource;
use App\Http\Resources\TeamSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'team' => TeamSummaryResource::make($this->whenLoaded('team')),
            'category' => Category::getDetail($this->category),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'levels' => LevelResource::collection($this->whenLoaded('levels')),
            'status' => $this->status,
            'is_active' => $this->getConfig('is_active', true),
            'enable_file_upload' => $this->getConfig('enable_file_upload', false),
            'is_file_upload_required' => $this->getConfig('is_file_upload_required', false),
            $this->mergeWhen($this->category == Category::ITEM_BASED, [
                'item_based_type' => $this->getConfig('item_based_type', 'item_with_quantity'),
            ]),
            $this->mergeWhen($this->category == Category::CONTACT_BASED, [
                'enable_contact_number' => $this->getConfig('enable_contact_number', false),
                'is_contact_number_required' => $this->getConfig('is_contact_number_required', false),
                'enable_email' => $this->getConfig('enable_email', false),
                'is_email_required' => $this->getConfig('is_email_required', false),
                'enable_website' => $this->getConfig('enable_website', false),
                'is_website_required' => $this->getConfig('is_website_required', false),
                'enable_tax_number' => $this->getConfig('enable_tax_number', false),
                'is_tax_number_required' => $this->getConfig('is_tax_number_required', false),
                'enable_address' => $this->getConfig('enable_address', false),
                'is_address_required' => $this->getConfig('is_address_required', false),
            ]),
            $this->mergeWhen($this->category == Category::PAYMENT_BASED, [
                'enable_invoice_number' => $this->getConfig('enable_invoice_number', false),
                'is_invoice_number_required' => $this->getConfig('is_invoice_number_required', false),
                'enable_invoice_date' => $this->getConfig('enable_invoice_date', false),
                'is_invoice_date_required' => $this->getConfig('is_invoice_date_required', false),
                'enable_payment_mode' => $this->getConfig('enable_payment_mode', false),
                'is_payment_mode_required' => $this->getConfig('is_payment_mode_required', false),
                'enable_payment_details' => $this->getConfig('enable_payment_details', false),
                'is_payment_details_required' => $this->getConfig('is_payment_details_required', false),
            ]),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
