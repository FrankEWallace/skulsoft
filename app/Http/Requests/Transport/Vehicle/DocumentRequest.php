<?php

namespace App\Http\Requests\Transport\Vehicle;

use App\Models\Document;
use App\Models\Media;
use App\Models\Option;
use App\Models\Transport\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DocumentRequest extends FormRequest
{
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
        return [
            'vehicle' => 'required',
            'type' => 'required',
            'title' => 'required|min:2|max:200',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Document)->getModelName();

            $vehicleDocumentUuid = $this->route('document');

            $vehicleDocumentType = Option::query()
                ->whereType('vehicle_document_type')
                ->whereUuid($this->type)
                ->getOrFail(__('transport.vehicle.document.props.type'), 'type');

            $vehicle = Vehicle::query()
                ->byTeam()
                ->whereUuid($this->vehicle)
                ->getOrFail(__('transport.vehicle.vehicle'), 'vehicle');

            $existingDocument = Document::query()
                ->whereHasMorph(
                    'documentable',
                    [Vehicle::class],
                    function (Builder $query) {
                        $query->whereUuid($this->vehicle);
                    }
                )
                ->when($vehicleDocumentUuid, function ($q, $vehicleDocumentUuid) {
                    $q->where('uuid', '!=', $vehicleDocumentUuid);
                })
                ->whereTitle($this->title)
                ->where('start_date', '=', $this->start_date)
                ->exists();

            if ($existingDocument) {
                $validator->errors()->add('title', trans('validation.unique', ['attribute' => __('transport.vehicle.document.props.title')]));
            }

            $attachedMedia = Media::whereModelType($mediaModel)
                ->whereToken($this->media_token)
                // ->where('meta->hash', $this->media_hash)
                ->where('meta->is_temp_deleted', false)
                ->where(function ($q) use ($vehicleDocumentUuid) {
                    $q->whereStatus(0)
                        ->when($vehicleDocumentUuid, function ($q) {
                            $q->orWhere('status', 1);
                        });
                })
                ->exists();

            if (! $attachedMedia) {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
            }

            $this->merge([
                'type_id' => $vehicleDocumentType->id,
                'vehicle_id' => $vehicle->id,
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
            'vehicle' => __('transport.vehicle.vehicle'),
            'type' => __('transport.vehicle.document.props.type'),
            'title' => __('transport.vehicle.document.props.title'),
            'description' => __('transport.vehicle.document.props.description'),
            'start_date' => __('transport.vehicle.document.props.start_date'),
            'end_date' => __('transport.vehicle.document.props.end_date'),
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
