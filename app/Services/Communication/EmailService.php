<?php

namespace App\Services\Communication;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Jobs\Communication\SendEmail;
use App\Models\Communication\Communication;
use App\Support\HasAudience;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmailService
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

        $communication->addMedia($request);

        \DB::commit();

        $this->sendEmail($communication);

        return $communication;
    }

    private function sendEmail(Communication $communication): void
    {
        $recipients = $communication->recipients;
        $chunkSize = 20;

        $communication->load('media');

        foreach (array_chunk($recipients, $chunkSize) as $chunk) {
            SendEmail::dispatch([
                'communication_id' => $communication->id,
                'recipients' => $chunk,
                'team_id' => auth()->user()->current_team_id,
            ]);
        }
    }

    private function getReceipients(Request $request): array
    {
        $contacts = $this->getContacts($request->all());

        $emails = $contacts->pluck('email')->toArray();

        $inclusion = $request->inclusion ?? [];
        $exclusion = $request->exclusion ?? [];

        foreach ($inclusion as $include) {
            $emails[] = $include;
        }

        $emails = array_diff($emails, $exclusion);

        $validatedEmails = [];
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validatedEmails[] = $email;
            }
        }

        return $validatedEmails;
    }

    private function formatParams(Request $request, ?Communication $communication = null): array
    {
        $receipients = $this->getReceipients($request);

        if (! count($receipients)) {
            throw ValidationException::withMessages(['recipients' => trans('communication.email.no_recipient_found')]);
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
            'content' => clean($request->content),
        ];

        if (! $communication) {
            $formatted['type'] = 'email';
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
