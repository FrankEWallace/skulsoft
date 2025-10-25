<x-print.layout type="full-page">
    @includeFirst([config('config.print.custom_path') . 'header', 'print.header'])

    <h2 class="heading">{{ trans('student.registration.registration') }}</h2>

    <table width="100%">
        <tr>
            <td><img src="{{ Arr::get($registration, 'contact.photo') }}" alt="Photo" width="100" height="auto">
            </td>
            <td class="text-right" valign="top" style="line-height: 1.5;">
                <strong>{{ trans('student.registration.props.code_number') }}:
                    {{ Arr::get($registration, 'code_number') }}</strong> <br />
                <strong>{{ trans('student.registration.props.date') }}:
                    {{ Arr::get($registration, 'date.formatted') }}</strong><br />
                <strong>{{ trans('academic.period.period') }}:
                    {{ Arr::get($registration, 'period.name') }}</strong><br />
                <strong>{{ trans('academic.course.course') }}:
                    {{ Arr::get($registration, 'course.name') }}</strong><br />
                <strong>{{ trans('student.enrollment_type.enrollment_type') }}:
                    {{ Arr::get($registration, 'enrollment_type.name') }}</strong>
            </td>
        </tr>
    </table>

    <table class="mt-4 table" style="table-layout: fixed;">
        <tr>
            <th>{{ trans('student.registration.props.stage') }}</th>
            <td>{{ Arr::get($registration, 'stage.name') }}</td>
            <th>{{ trans('student.registration.props.assigned_to') }}</th>
            <td>{{ Arr::get($registration, 'employee.name') }}</td>
            <th></th>
            <td></td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.name') }}</th>
            <td>{{ Arr::get($registration, 'contact.name') }}</td>
            <th>{{ trans('contact.props.contact_number') }}</th>
            <td>{{ Arr::get($registration, 'contact.contact_number') }}</td>
            <th>{{ trans('contact.props.email') }}</th>
            <td>{{ Arr::get($registration, 'contact.email') }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.alternate_contact_number') }}</th>
            <td>{{ Arr::get($registration, 'contact.alternate_records.contact_number') }}
            <th>{{ trans('contact.props.alternate_email') }}</th>
            <td>{{ Arr::get($registration, 'contact.alternate_records.email') }}</td>
            </td>
            <th></th>
            <td></td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.birth_date') }}</th>
            <td>{{ Arr::get($registration, 'contact.birth_date.formatted') }}</td>
            <th>{{ trans('contact.props.gender') }}</th>
            <td>{{ Arr::get($registration, 'contact.gender.label') }}</td>
            <th>{{ trans('contact.props.locality') }}</th>
            <td>{{ Arr::get($registration, 'contact.locality.label') }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.category.category') }}</th>
            <td>{{ Arr::get($registration, 'contact.category.name') }}</td>
            <th>{{ trans('contact.caste.caste') }}</th>
            <td>{{ Arr::get($registration, 'contact.caste.name') }}</td>
            <th>{{ trans('contact.religion.religion') }}</th>
            <td>{{ Arr::get($registration, 'contact.caste.name') }}</td>
        </tr>
        <tr>
            <th>{{ config('config.student.unique_id_number1_label') }}</th>
            <td>{{ Arr::get($registration, 'contact.unique_id_number1') }}</td>
            <th>{{ config('config.student.unique_id_number2_label') }}</th>
            <td>{{ Arr::get($registration, 'contact.unique_id_number2') }}</td>
            <th>{{ config('config.student.unique_id_number3_label') }}</th>
            <td>{{ Arr::get($registration, 'contact.unique_id_number3') }}</td>
        </tr>
        <tr>
            <th>{{ config('config.student.unique_id_number4_label') }}</th>
            <td>{{ Arr::get($registration, 'contact.unique_id_number4') }}</td>
            <th>{{ config('config.student.unique_id_number5_label') }}</th>
            <td>{{ Arr::get($registration, 'contact.unique_id_number5') }}</td>
            <th></th>
            <td></td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.birth_place') }}</th>
            <td>{{ Arr::get($registration, 'contact.birth_place') }}</td>
            <th>{{ trans('contact.props.nationality') }}</th>
            <td>{{ Arr::get($registration, 'contact.nationality') }}</td>
            <th>{{ trans('contact.props.mother_tongue') }}</th>
            <td>{{ Arr::get($registration, 'contact.mother_tongue') }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.blood_group') }}</th>
            <td>{{ Arr::get($registration, 'contact.blood_group.label') }}</td>
            <th>{{ trans('contact.props.marital_status') }}</th>
            <td>{{ Arr::get($registration, 'contact.marital_status.label') }}</td>
            <th>
                @foreach (Arr::get($registration, 'custom_fields', []) as $customField)
                    <p>{{ Arr::get($customField, 'label') }}</p>
                @endforeach
            </th>
            <td>
                @foreach (Arr::get($registration, 'custom_fields', []) as $customField)
                    <p>{{ Arr::get($customField, 'formatted_value') }}</p>
                @endforeach
            </td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.present_address') }}</th>
            <td style="max-width: 20%;">{{ Arr::get($registration, 'contact.present_address_display') }}</td>
            <th>{{ trans('contact.props.permanent_address') }}</th>
            <td style="max-width: 20%;">{{ Arr::get($registration, 'contact.permanent_address_display') }}</td>
            <th></th>
            <td></td>
        </tr>
    </table>

    <h2 class="sub-heading">{{ trans('guardian.guardian') }}</h2>

    <table class="mt-4 table">
        <tr>
            <th>{{ trans('contact.props.name') }}</th>
            <th>{{ trans('contact.props.relation') }}</th>
            <th>{{ trans('contact.props.contact_number') }}</th>
            <th>{{ trans('contact.props.occupation') }}</th>
        </tr>
        @foreach (Arr::get($registration, 'contact.guardians', []) as $guardian)
            <tr>
                <td>{{ Arr::get($guardian, 'contact.name') }}</td>
                <td>{{ Arr::get($guardian, 'relation.label') }}</td>
                <td>{{ Arr::get($guardian, 'contact.contact_number') }}</td>
                <td>{{ Arr::get($guardian, 'contact.occupation') }}</td>
            </tr>
        @endforeach
    </table>

    <h2 class="sub-heading">{{ trans('student.qualification.qualification') }}</h2>

    <table class="mt-4 table">
        <tr>
            <th>{{ trans('academic.course.course') }}</th>
            <th>{{ trans('student.qualification_level.qualification_level') }}</th>
            <th>{{ trans('student.qualification.props.institute') }}</th>
            <th>{{ trans('general.period') }}</th>
            <th>{{ trans('student.qualification.props.result') }}</th>
        </tr>
        @foreach (Arr::get($registration, 'contact.qualifications', []) as $qualification)
            <tr>
                <td>{{ Arr::get($qualification, 'course') }} <span
                        class="font-90pc">{{ Arr::get($qualification, 'session') }}</span></td>
                <td>{{ Arr::get($qualification, 'level.name') }}</td>
                <td>{{ Arr::get($qualification, 'institute') }}</td>
                <td>{{ Arr::get($qualification, 'period') }}</td>
                <td>{{ Arr::get($qualification, 'result.label') }}
                    @if (Arr::get($qualification, 'result.value') == 'pass')
                        ({{ Arr::get($qualification, 'percentage') }}%)
                    @elseif (Arr::get($qualification, 'result.value') == 'reappear')
                        <span class="text-red-500">({{ Arr::get($qualification, 'failed_subjects') }})</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <h2 class="sub-heading">{{ trans('student.document.document') }}</h2>

    <table class="mt-4 table">
        <tr>
            <th>{{ trans('student.document.props.title') }}</th>
            <th>{{ trans('student.document_type.document_type') }}</th>
            <th>{{ trans('general.period') }}</th>
        </tr>
        @foreach (Arr::get($registration, 'contact.documents', []) as $document)
            <tr>
                <td>{{ Arr::get($document, 'title') }}</td>
                <td>{{ Arr::get($document, 'type.name') }}</td>
                <td>{{ Arr::get($document, 'period') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="mt-8">
        <p class="text-right">{{ trans('print.authorized_signatory') }}</p>
    </div>
    <div class="mt-4">
        <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}
        </p>
    </div>
</x-print.layout>
