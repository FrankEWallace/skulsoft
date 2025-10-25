<?php

namespace App\Services\Communication;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Http\Resources\Config\WhatsAppTemplateResource;
use App\Models\Communication\Communication;
use App\Models\Config\Template;
use App\Support\HasAudience;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WhatsAppService
{
    use HasAudience;

    public function preRequisite(Request $request): array
    {
        $studentAudienceTypes = StudentAudienceType::getOptions();

        $employeeAudienceTypes = EmployeeAudienceType::getOptions();

        $templates = WhatsAppTemplateResource::collection(Template::query()
            ->where('type', 'whatsapp')
            ->get());

        return compact('studentAudienceTypes', 'employeeAudienceTypes', 'templates');
    }

    public function create(Request $request): Communication
    {
        throw ValidationException::withMessages([
            'message' => trans('general.errors.feature_under_development'),
        ]);

        \DB::beginTransaction();

        $communication = Communication::forceCreate($this->formatParams($request));

        $this->storeAudience($communication, $request->all());

        \DB::commit();

        $this->sendWhatsApp($communication);

        return $communication;
    }

    private function sendWhatsApp(Communication $communication): void
    {
        $recipients = $communication->recipients;
        $chunkSize = 20;

        foreach (array_chunk($recipients, $chunkSize) as $chunk) {
            // send whatsapp
        }
    }

    private function getReceipients(Request $request): array
    {
        $contacts = $this->getContacts($request->all());

        $contactNumbers = $contacts->pluck('contact_number')->toArray();

        $inclusion = $request->inclusion ?? [];
        $exclusion = $request->exclusion ?? [];

        foreach ($inclusion as $include) {
            $contactNumbers[] = $include;
        }

        $contactNumbers = array_diff($contactNumbers, $exclusion);

        return $contactNumbers;
    }

    private function formatParams(Request $request, ?Communication $communication = null): array
    {
        $receipients = $this->getReceipients($request);

        if (! count($receipients)) {
            throw ValidationException::withMessages(['recipients' => trans('communication.whatsapp.no_recipient_found')]);
        }

        $formatted = [
            'subject' => $request->subject,
            'lists' => [
                'inclusion' => $request->inclusion,
                'exclusion' => $request->exclusion,
            ],
            'audience' => [
                'student_type' => $request->student_audience_type,
                'employee_type' => $request->employee_audience_type,
            ],
            'recipients' => $receipients,
            'content' => $request->content,
        ];

        if (! $communication) {
            $formatted['type'] = 'whatsapp';
            $formatted['user_id'] = auth()->id();
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        $meta = $communication?->meta ?? [];

        $meta['recipient_count'] = count($receipients);

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function deletable(Communication $announcement): void
    {
        throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
    }
}
