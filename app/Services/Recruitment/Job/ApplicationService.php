<?php

namespace App\Services\Recruitment\Job;

use App\Actions\CreateContact;
use App\Models\Contact;
use App\Models\Employee\Employee;
use App\Models\Recruitment\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function create(Request $request)
    {
        $vacancy = $request->vacancy;

        $contact = Contact::query()
            ->byTeam($vacancy->team_id)
            ->whereFirstName($request->first_name)
            ->whereMiddleName($request->middle_name)
            ->whereThirdName($request->third_name)
            ->whereLastName($request->last_name)
            ->where(function ($q) use ($request) {
                $q->where('email', $request->email)
                    ->orWhere('contact_number', $request->contact_number);
            })
            ->first();

        if ($contact) {
            $existingEmployee = Employee::query()
                ->byTeam($vacancy->team_id)
                ->whereContactId($contact->id)
                ->whereNotNull('leaving_date')
                ->exists();

            if ($existingEmployee) {
                throw ValidationException::withMessages(['message' => trans('recruitment.application.could_not_perform_if_existing_employee')]);
            }

            $existingApplicant = Application::query()
                ->whereContactId($contact->id)
                ->whereVacancyId($vacancy->id)
                ->exists();

            if ($existingApplicant) {
                throw ValidationException::withMessages(['message' => trans('recruitment.application.duplicate_application')]);
            }
        }

        \DB::beginTransaction();

        $params = $request->all();
        $params['source'] = 'job_applicant';

        $contact = (new CreateContact)->execute($params);

        $contact->address = [
            'present' => $request->present_address,
        ];
        $contact->save();

        $application = Application::forceCreate([
            'contact_id' => $contact->id,
            'vacancy_id' => $vacancy->id,
            'designation_id' => $request->designation_id,
            'application_date' => today()->toDateString(),
            'availability_date' => $request->availability_date,
            'cover_letter' => clean($request->cover_letter),
            'qualification_summary' => $request->qualification_summary,
        ]);

        $application->addMedia($request);

        \DB::commit();
    }
}
