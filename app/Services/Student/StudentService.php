<?php

namespace App\Services\Student;

use App\Enums\BloodGroup;
use App\Enums\CustomFieldForm;
use App\Enums\Gender;
use App\Enums\Locality;
use App\Enums\MaritalStatus;
use App\Enums\OptionType;
use App\Enums\Student\AdmissionType;
use App\Enums\Student\StudentStatus;
use App\Http\Resources\CustomFieldResource;
use App\Http\Resources\OptionResource;
use App\Models\CustomField;
use App\Models\GroupMember;
use App\Models\Option;
use App\Models\Student\Student;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StudentService
{
    public function preRequisite(): array
    {
        $genders = Gender::getOptions();

        $bloodGroups = BloodGroup::getOptions();

        $localities = Locality::getOptions();

        $maritalStatuses = MaritalStatus::getOptions();

        $categories = config('config.contact.enable_category_field') ? OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::MEMBER_CATEGORY->value)
            ->get()) : [];

        $castes = config('config.contact.enable_caste_field') ? OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::MEMBER_CASTE->value)
            ->get()) : [];

        $religions = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::RELIGION->value)
            ->get());

        $enrollmentTypes = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_ENROLLMENT_TYPE->value)
            ->get());

        $enrollmentStatuses = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_ENROLLMENT_STATUS->value)
            ->get());

        $groups = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_GROUP->value)
            ->get());

        $customFields = CustomFieldResource::collection(CustomField::query()
            ->byTeam()
            ->whereForm(CustomFieldForm::STUDENT)
            ->orderBy('position')
            ->get());

        $statuses = StudentStatus::getOptions();

        array_push($statuses, [
            'label' => trans('student.alumni.alumni'),
            'value' => 'alumni',
        ]);

        $admissionTypes = AdmissionType::getOptions();

        return compact('genders', 'bloodGroups', 'localities', 'maritalStatuses', 'categories', 'castes', 'religions', 'enrollmentTypes', 'enrollmentStatuses', 'statuses', 'customFields', 'groups', 'admissionTypes');
    }

    // public function create(Request $request): Contact
    // {
    //     \DB::beginTransaction();

    //     $contact = (new CreateContact)->execute($request->all());

    //     \DB::commit();

    //     return $contact;
    // }

    // public function update(Request $request, Contact $contact): void
    // {
    //     $data = $request->secured();

    //     $data['address'] = $contact->address;

    //     $request->whenHas('present_address', function ($presentAddress) use (&$data) {
    //         $data['address']['present'] = [
    //             'address_line1' => Arr::get($presentAddress, 'address_line1'),
    //             'address_line2' => Arr::get($presentAddress, 'address_line2'),
    //             'city' => Arr::get($presentAddress, 'city'),
    //             'state' => Arr::get($presentAddress, 'state'),
    //             'zipcode' => Arr::get($presentAddress, 'zipcode'),
    //             'country' => Arr::get($presentAddress, 'country'),
    //         ];
    //     });

    //     $request->whenHas('permanent_address', function ($permanentAddress) use (&$data) {
    //         $data['address']['permanent'] = [
    //             'same_as_present_address' => (bool) Arr::get($permanentAddress, 'same_as_present_address'),
    //             'address_line1' => Arr::get($permanentAddress, 'address_line1'),
    //             'address_line2' => Arr::get($permanentAddress, 'address_line2'),
    //             'city' => Arr::get($permanentAddress, 'city'),
    //             'state' => Arr::get($permanentAddress, 'state'),
    //             'zipcode' => Arr::get($permanentAddress, 'zipcode'),
    //             'country' => Arr::get($permanentAddress, 'country'),
    //         ];
    //     });

    //     \DB::beginTransaction();

    //     $contact->update($data);

    //     \DB::commit();
    // }

    // public function deletable(Contact $contact, $validate = false): ?bool
    // {
    //     return true;
    // }

    public function deletable(Student $student, $validate = false): ?bool
    {
        $records = Student::query()
            ->where('students.id', '!=', $student->id)
            ->where('students.contact_id', $student->contact_id)
            ->where('students.admission_id', $student->admission_id)
            ->join('batches', 'batches.id', '=', 'students.batch_id')
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->join('periods', 'periods.id', '=', 'students.period_id')
            ->select('students.uuid', 'courses.name as course_name', 'periods.name as period_name', 'batches.name as batch_name')
            ->get();

        if ($records->count() > 0) {
            throw ValidationException::withMessages(['message' => trans('student.could_not_delete_with_multiple_records')]);
        }

        $feeSummary = $student->getFeeSummary();

        if (Arr::get($feeSummary, 'paid_fee')?->value > 0) {
            throw ValidationException::withMessages(['message' => trans('student.could_not_delete_with_paid_fee')]);
        }

        return true;
    }

    public function delete(Student $student): void
    {
        $student = Student::find($student->id);

        \DB::beginTransaction();

        $admission = $student->admission;

        GroupMember::query()
            ->whereModelType('Student')
            ->whereModelId($student->id)
            ->delete();

        $student->delete();

        $admission->delete();

        \DB::commit();
    }
}
