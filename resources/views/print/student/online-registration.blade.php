<x-print.layout type="centered">
    <table width="100%" border="0" cellspacing="4" cellpadding="0">
        <tr>
            <td width="33%" valign="top">
                <img src="{{ url(config('config.assets.logo')) }}" width="150" />
            </td>
            <td valign="top" align="right">
                <div class="heading text-right">{{ config('config.team.config.name') }}</div>
                @if (config('config.team.config.title1'))
                    <div class="sub-heading mt-1 text-right">{{ config('config.team.config.title1') }}</div>
                @endif
                @if (config('config.team.config.title2'))
                    <div class="sub-heading mt-1 text-right">{{ config('config.team.config.title2') }}</div>
                @endif
                @if (config('config.team.config.title3'))
                    <div class="sub-heading mt-1 text-right">{{ config('config.team.config.title3') }}</div>
                @endif
                @if (config('config.team.config.email') || config('config.team.config.phone'))
                    <div class="mt-1 text-right">
                        @if (config('config.team.config.phone'))
                            <span>{{ config('config.team.config.email') }}</span>
                        @endif
                        @if (config('config.team.config.phone'))
                            <span>{{ config('config.team.config.phone') }}</span>
                        @endif
                    </div>
                @endif
                @if (config('config.team.config.website'))
                    <div class="mt-1 text-right">{{ config('config.team.config.website') }}</div>
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <h2 class="heading text-center">
                    {{ trans('student.online_registration.application') }}
                </h2>
                <p class="text-center">{{ $registration->course->division?->program?->name }}
                    {{ $registration->period->name }}
                </p>
                <p class="text-center font-110pc">
                    @if ($registration->status->value == 'rejected')
                        <span style="color: red;">({{ trans('student.registration.statuses.rejected') }})</span>
                    @endif
                    @if ($registration->status->value == 'pending')
                        <span style="color: orange;">({{ trans('student.registration.statuses.pending') }})</span>
                    @endif
                </p>
            </td>
        </tr>
    </table>
    <table class="mt-2" width="100%" border="0" cellspacing="4" cellpadding="0">
        <tr>
            <td width="50%" valign="top">
                <div class="sub-heading-left">{{ trans('student.online_registration.application_number') }}:
                    {{ $registration->getMeta('application_number') }}</div>
            </td>
            <td width="50%" valign="top">
                <div class="sub-heading text-right">{{ trans('student.registration.props.date') }}:
                    {{ $registration->date->formatted }}</div>
            </td>
        </tr>
    </table>

    <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
        <tr>
            <th>{{ trans('contact.props.name') }}</th>
            <td class="text-right">{{ $registration->contact->name }}</td>
            <th>{{ trans('contact.props.contact_number') }}</th>
            <td class="text-right">{{ $registration->contact->contact_number }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.email') }}</th>
            <td class="text-right">{{ $registration->contact->email }}</td>
            <th>{{ trans('contact.props.gender') }}</th>
            <td class="text-right">{{ App\Enums\Gender::getDetail($registration->contact->gender)['label'] ?? '' }}
            </td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.birth_date') }}</th>
            <td class="text-right">{{ $registration->contact->birth_date->formatted }}</td>
            <th>{{ trans('contact.props.birth_place') }}</th>
            <td class="text-right">{{ $registration->contact->birth_place }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.father_name') }}</th>
            <td class="text-right">{{ $registration->contact->father_name }}</td>
            <th>{{ trans('contact.props.mother_name') }}</th>
            <td class="text-right">{{ $registration->contact->mother_name }}</td>
        </tr>
        <tr>
            <th>{{ trans('academic.program.program') }}</th>
            <td class="text-right">{{ $registration->course->division?->program?->name }}</td>
            <th>{{ trans('academic.period.period') }}</th>
            <td class="text-right">{{ $registration->period->name }}</td>
        </tr>
        <tr>
            <th>{{ trans('academic.course.course') }}</th>
            <td class="text-right">{{ $registration->course->name }}</td>
            <th></th>
            <td></td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.nationality') }}</th>
            <td class="text-right">{{ $registration->contact->nationality }}</td>
            <th>{{ trans('contact.props.mother_tongue') }}</th>
            <td class="text-right">{{ $registration->contact->mother_tongue }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.blood_group') }}</th>
            <td class="text-right">
                {{ App\Enums\BloodGroup::getDetail($registration->contact->blood_group)['label'] ?? '' }}
            </td>
            <th>{{ trans('contact.props.marital_status') }}</th>
            <td class="text-right">
                {{ App\Enums\MaritalStatus::getDetail($registration->contact->marital_status)['label'] ?? '' }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.category.category') }}</th>
            <td class="text-right">{{ $registration->contact->category?->name }}</td>
            <th>{{ trans('contact.caste.caste') }}</th>
            <td class="text-right">{{ $registration->contact->caste?->name }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.religion.religion') }}</th>
            <td class="text-right">{{ $registration->contact->religion?->name }}</td>
            <th></th>
            <td></td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.alternate_contact_number') }}</th>
            <td class="text-right">{{ Arr::get($registration->contact->alternate_records, 'contact_number') }}</td>
            <th>{{ trans('contact.props.alternate_email') }}</th>
            <td class="text-right">{{ Arr::get($registration->contact->alternate_records, 'email') }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.present_address') }}</th>
            <td class="text-right">{{ Arr::toAddress($registration->contact->present_address) }}</td>
            <th>{{ trans('contact.props.permanent_address') }}</th>
            <td class="text-right">{{ Arr::toAddress($registration->contact->permanent_address) }}</td>
        </tr>
    </table>

    <div class="mt-4">
        <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}</p>
    </div>
</x-print.layout>
