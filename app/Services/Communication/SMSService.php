<?php

namespace App\Services\Communication;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Jobs\Communication\SendSMS;
use App\Models\Communication\Communication;
use App\Support\HasAudience;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SMSService
{
    use HasAudience;

    public function preRequisite(Request $request): array
    {
        $studentAudienceTypes = StudentAudienceType::getOptions();

        $employeeAudienceTypes = EmployeeAudienceType::getOptions();

        return compact('studentAudienceTypes', 'employeeAudienceTypes');
    }

    public function create(Request $request): Communication
    {
        \DB::beginTransaction();

        $communication = Communication::forceCreate($this->formatParams($request));

        $this->storeAudience($communication, $request->all());

        \DB::commit();

        $this->sendSMS($communication);

        return $communication;
    }

    private function sendSMS(Communication $communication): void
    {
        $recipients = $communication->recipients;
        $chunkSize = 20;

        foreach (array_chunk($recipients, $chunkSize) as $chunk) {
            SendSMS::dispatch([
                'communication_id' => $communication->id,
                'recipients' => $chunk,
                'team_id' => auth()->user()->current_team_id,
            ]);
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
            throw ValidationException::withMessages(['recipients' => trans('communication.sms.no_recipient_found')]);
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
            $formatted['type'] = 'sms';
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
