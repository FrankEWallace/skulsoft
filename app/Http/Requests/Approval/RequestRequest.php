<?php

namespace App\Http\Requests\Approval;

use App\Concerns\SimpleValidation;
use App\Enums\Finance\LedgerGroup;
use App\Enums\OptionType;
use App\Models\Approval\Request as ApprovalRequest;
use App\Models\Approval\Type;
use App\Models\Employee\Employee;
use App\Models\Finance\Ledger;
use App\Models\Media;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class RequestRequest extends FormRequest
{
    use SimpleValidation;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'title' => 'required|min:2|max:100',
            'type.uuid' => 'required|uuid',
            'priority' => 'nullable|uuid',
            'group' => 'nullable|uuid',
            'nature' => 'nullable|uuid',
            'due_date' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'description' => 'nullable|min:2|max:1000',
        ];

        $type = Type::query()
            ->byTeam()
            ->whereUuid($this->input('type.uuid'))
            ->getOrFail(trans('approval.type.type'), 'type');

        if ($type->category->value == 'item_based') {
            $rules['items'] = 'required|array';
            $rules['items.*.uuid'] = 'required|uuid|distinct';
            $rules['items.*.item'] = 'required|string|max:100';

            if ($type->getConfig('item_based_type') == 'item_with_quantity') {
                $rules['items.*.quantity'] = 'required|numeric|min:0';
                $rules['items.*.unit'] = 'required|string|max:100';
                $rules['items.*.price'] = 'required|numeric|min:0';
            } elseif ($type->getConfig('item_based_type') == 'item_without_quantity') {
                $rules['items.*.amount'] = 'required|numeric|min:0';
            }
            $rules['items.*.description'] = 'nullable|string|max:200';
        } elseif ($type->category->value == 'payment_based') {
            $rules['vendor'] = 'nullable|uuid';
            $rules['payment.payee'] = 'required|string|max:100';
            $rules['payment.amount'] = 'required|numeric|min:0';
            $rules['payment.invoice_number'] = 'nullable|max:200';
            if ($type->getConfig('enable_invoice_number') && $type->getConfig('is_invoice_number_required')) {
                $rules['payment.invoice_number'] = 'required|max:200';
            }
            $rules['payment.invoice_date'] = 'nullable|date_format:Y-m-d';
            if ($type->getConfig('enable_invoice_date') && $type->getConfig('is_invoice_date_required')) {
                $rules['payment.invoice_date'] = 'required|date_format:Y-m-d';
            }
            $rules['payment.mode'] = 'nullable|string|max:100';
            if ($type->getConfig('enable_payment_mode') && $type->getConfig('is_payment_mode_required')) {
                $rules['payment.mode'] = 'required|string|max:100';
            }
            $rules['payment.details'] = 'nullable|string|max:200';
            if ($type->getConfig('enable_payment_details') && $type->getConfig('is_payment_details_required')) {
                $rules['payment.details'] = 'required|string|max:200';
            }
        } elseif ($type->category->value == 'contact_based') {
            $rules['contact.name'] = 'required|string|max:100';
            $rules['contact.contact_number'] = 'nullable|string|max:20';
            if ($type->getConfig('enable_contact_contact_number') && $type->getConfig('is_contact_contact_number_required')) {
                $rules['contact.contact_number'] = 'required|string|max:20';
            }
            $rules['contact.email'] = 'nullable|email|max:100';
            if ($type->getConfig('enable_contact_email') && $type->getConfig('is_contact_email_required')) {
                $rules['contact.email'] = 'required|email|max:100';
            }
            $rules['contact.website'] = 'nullable|url|max:100';
            if ($type->getConfig('enable_contact_website') && $type->getConfig('is_contact_website_required')) {
                $rules['contact.website'] = 'required|url|max:100';
            }
            $rules['contact.tax_number'] = 'nullable|string|max:100';
            if ($type->getConfig('enable_contact_tax_number') && $type->getConfig('is_contact_tax_number_required')) {
                $rules['contact.tax_number'] = 'required|string|max:100';
            }
            $rules['contact.address.address_line1'] = 'nullable|string|max:100';
            $rules['contact.address.address_line2'] = 'nullable|string|max:100';
            $rules['contact.address.city'] = 'nullable|string|max:100';
            $rules['contact.address.state'] = 'nullable|string|max:100';
            $rules['contact.address.country'] = 'nullable|string|max:100';
            $rules['contact.address.zipcode'] = 'nullable|string|max:100';
            if ($type->getConfig('enable_contact_address') && $type->getConfig('is_contact_address_required')) {
                $rules['contact.address.address_line1'] = 'required|string|max:100';
                $rules['contact.address.address_line2'] = 'nullable|string|max:100';
                $rules['contact.address.city'] = 'required|string|max:100';
                $rules['contact.address.state'] = 'required|string|max:100';
                $rules['contact.address.country'] = 'required|string|max:100';
                $rules['contact.address.zipcode'] = 'required|string|max:100';
            }
        } elseif ($type->category->value == 'other') {
            $rules['purpose'] = 'required|max:2000';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {

            $validator->after(function ($validator) {
                $this->change($validator, 'contact.name', 'contactName');
                $this->change($validator, 'contact.contact_number', 'contactContactNumber');
                $this->change($validator, 'contact.email', 'contactEmail');
                $this->change($validator, 'contact.website', 'contactWebsite');
                $this->change($validator, 'contact.tax_number', 'contactTaxNumber');
                $this->change($validator, 'contact.address.address_line1', 'address_line1');
                $this->change($validator, 'contact.address.address_line2', 'address_line2');
                $this->change($validator, 'contact.address.city', 'city');
                $this->change($validator, 'contact.address.state', 'state');
                $this->change($validator, 'contact.address.zipcode', 'zipcode');
                $this->change($validator, 'contact.address.country', 'country');

                $this->change($validator, 'payment.payee', 'paymentPayee');
                $this->change($validator, 'payment.amount', 'paymentAmount');
                $this->change($validator, 'payment.invoice_number', 'paymentInvoiceNumber');
                $this->change($validator, 'payment.invoice_date', 'paymentInvoiceDate');
                $this->change($validator, 'payment.mode', 'paymentMode');
                $this->change($validator, 'payment.details', 'paymentDetails');
            });

            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('request');

            $mediaModel = (new ApprovalRequest)->getModelName();

            $type = Type::query()
                ->with('levels')
                ->byTeam()
                ->whereUuid($this->input('type.uuid'))
                ->getOrFail(trans('approval.type.type'), 'type');

            $priority = $this->priority ? Option::query()
                ->byTeam()
                ->where('type', OptionType::APPROVAL_REQUEST_PRIORITY)
                ->whereUuid($this->priority)
                ->getOrFail(trans('approval.request.priority.priority'), 'priority') : null;

            $group = $this->group ? Option::query()
                ->byTeam()
                ->where('type', OptionType::APPROVAL_REQUEST_GROUP)
                ->whereUuid($this->group)
                ->getOrFail(trans('approval.request.group.group'), 'group') : null;

            $nature = $this->nature ? Option::query()
                ->byTeam()
                ->where('type', OptionType::APPROVAL_REQUEST_NATURE)
                ->whereUuid($this->nature)
                ->getOrFail(trans('approval.request.nature.nature'), 'nature') : null;

            $vendor = ($this->input('type.category.value') == 'payment_based' && $this->vendor) ? Ledger::query()
                ->byTeam()
                ->whereHas('type', function ($q) {
                    $q->where('type', LedgerGroup::SUNDRY_CREDITOR);
                })
                ->whereUuid($this->vendor)
                ->getOrFail(trans('inventory.vendor.vendor'), 'vendor') : null;

            $existingRequests = ApprovalRequest::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereTitle($this->title)
                ->where('date', today()->toDateString())
                ->whereRequestUserId(auth()->id())
                ->exists();

            if ($existingRequests) {
                $validator->errors()->add('title', trans('global.duplicate', ['attribute' => __('approval.request.request')]));
            }

            $levels = [];
            foreach ($type->levels as $level) {
                $employee = Employee::query()
                    ->select('employees.id', 'contacts.user_id', 'contacts.email')
                    ->join('contacts', 'contacts.id', '=', 'employees.contact_id')
                    ->where('employees.id', $level->employee_id)
                    ->first();

                if (! $employee) {
                    $validator->errors()->add('employee', trans('global.could_not_find', ['attribute' => __('employee.employee')]));
                }

                $levels[] = [
                    'user_id' => $employee->user_id,
                    'email' => $employee->email,
                ];
            }

            if ($type->getConfig('enable_file_upload') && $type->getConfig('is_file_upload_required')) {
                $attachedMedia = Media::whereModelType($mediaModel)
                    ->whereToken($this->media_token)
                    // ->where('meta->hash', $this->media_hash)
                    ->where('meta->is_temp_deleted', false)
                    ->where(function ($q) use ($uuid) {
                        $q->whereStatus(0)
                            ->when($uuid, function ($q) {
                                $q->orWhere('status', 1);
                            });
                    })
                    ->exists();

                if (! $attachedMedia) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
                }
            }

            if ($this->input('type.category.value') == 'contact_based') {
                $this->merge([
                    'contact' => Arr::only($this->input('contact'), ['name', 'contact_number', 'email', 'website', 'tax_number', 'address']),
                ]);
            } elseif ($this->input('type.category.value') == 'payment_based') {
                $this->merge([
                    'payment' => Arr::only($this->input('payment'), ['vendor', 'payee', 'amount', 'invoice_number', 'invoice_date', 'mode', 'details']),
                ]);
            }

            if ($this->input('type.category.value') == 'item_based' && $type->getConfig('item_based_type') == 'item_with_quantity') {
                $units = Option::query()
                    ->where('type', OptionType::UNIT)
                    ->get();

                foreach ($this->input('items') as $index => $item) {
                    $unit = $units->firstWhere('name', Arr::get($item, 'unit'));

                    if (! $unit) {
                        $validator->errors()->add("items.{$index}.unit", trans('global.could_not_find', ['attribute' => __('inventory.unit.unit')]));
                    }
                }
            }

            $this->merge([
                'levels' => $levels,
                'type_id' => $type->id,
                'type' => $type,
                'vendor_id' => $vendor?->id,
                'priority_id' => $priority?->id,
                'group_id' => $group?->id,
                'nature_id' => $nature?->id,
            ]);
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'title' => __('approval.request.props.title'),
            'type' => __('approval.type.type'),
            'priority' => __('approval.request.priority.priority'),
            'due_date' => __('approval.request.props.due_date'),
            'description' => __('approval.request.props.description'),
            'purpose' => __('approval.request.props.purpose'),
            'items.*.item' => __('inventory.item'),
            'items.*.quantity' => __('inventory.stock_item.props.quantity'),
            'items.*.unit' => __('inventory.stock_item.props.unit'),
            'items.*.price' => __('inventory.stock_item.props.price'),
            'items.*.description' => __('approval.request.props.description'),
            'contact.name' => __('contact.props.name'),
            'contact.contact_number' => __('contact.props.contact_number'),
            'contact.email' => __('contact.props.email'),
            'contact.website' => __('contact.props.website'),
            'contact.tax_number' => __('contact.props.tax_number'),
            'contact.address.address_line1' => __('contact.props.address.address_line1'),
            'contact.address.address_line2' => __('contact.props.address.address_line2'),
            'contact.address.city' => __('contact.props.address.city'),
            'contact.address.state' => __('contact.props.address.state'),
            'contact.address.zipcode' => __('contact.props.address.zipcode'),
            'contact.address.country' => __('contact.props.address.country'),
            'payment.payee' => __('finance.transaction.props.payee'),
            'payment.amount' => __('finance.transaction.props.amount'),
            'payment.invoice_number' => __('finance.transaction.props.invoice_number'),
            'payment.invoice_date' => __('finance.transaction.props.invoice_date'),
            'payment.mode' => __('finance.transaction.props.mode'),
            'payment.details' => __('finance.transaction.props.details'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
