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
                    {{ trans('finance.fee_structure.installment') }}
                </h2>
                <p class="text-center">{{ $student->batch->course->division?->program?->name }}
                    {{ $student->period->name }}
                </p>
            </td>
        </tr>
    </table>

    <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
        <tr>
            <th>{{ trans('student.props.name') }}</th>
            <td class="text-right">{{ $student->name }}</td>
            <th>{{ trans('student.admission.props.code_number') }}</th>
            <td class="text-right">{{ $student->code_number }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.father_name') }}</th>
            <td class="text-right">{{ $student->father_name }}</td>
            <th>{{ trans('contact.props.contact_number') }}</th>
            <td class="text-right">{{ $student->contact_number }}</td>
        </tr>
        <tr>
            <th>{{ trans('academic.course.course') }}</th>
            <td class="text-right">
                {{ $student->course_name . ' ' . $student->batch_name }} <br />
                <span class="font-90pc"></span>
            </td>
            <th>{{ trans('contact.props.birth_date') }}</th>
            <td class="text-right">{{ \Cal::date($student->birth_date)->formatted }}</td>
        </tr>
    </table>

    <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
        <thead>
            <tr>
                <th>
                    {{ $fee->installment->title }}
                </th>
                <th>
                    {{ trans('finance.fee_structure.props.due_date') }}:
                    {{ $fee->getDueDate()?->formatted }}
                </th>
            </tr>
        </thead>
        @if ($fee->transport_circle_id || $fee->fee_concession_id)
            <tbody>
                <tr>
                    @if ($fee->transport_circle_id)
                        <td>
                            {{ trans('transport.circle.circle') }}: {{ $fee->transportCircle->name }}
                        </td>
                    @endif
                    @if ($fee->fee_concession_id)
                        <td>
                            {{ trans('finance.fee_concession.fee_concession') }}: {{ $fee->concession->name }}
                        </td>
                    @endif
                </tr>
            </tbody>
        @endif
    </table>

    <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
        <thead>
            <tr>
                <th>
                    {{ trans('finance.fee_head.fee_head') }}
                </th>
                <th class="text-right">
                    {{ trans('finance.fee_structure.props.amount') }}
                </th>
                <th class="text-right">
                    {{ trans('finance.fee.paid') }}
                </th>
                <th class="text-right">
                    {{ trans('finance.fee.balance') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($fee->records as $feeRecord)
                <tr>
                    <td>
                        @if ($feeRecord->fee_head_id)
                            {{ $feeRecord->head?->name }}
                        @else
                            {{ $feeRecord->getDefaultFeeHeadName() }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if ($feeRecord->concession->value)
                            <span style="text-decoration: line-through"> {{ $feeRecord->amount->formatted }}</span>
                            {{ $feeRecord->getBalance()->formatted }}
                        @else
                            {{ $feeRecord->amount->formatted }}
                        @endif
                    </td>
                    <td class="text-right">
                        {{ $feeRecord->paid->formatted }}
                    </td>
                    <td class="text-right">
                        {{ $feeRecord->getBalance()?->formatted }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>{{ trans('general.total') }}</td>
                <td class="text-right">{{ $fee->total->formatted }}</td>
                <td class="text-right">{{ $fee->paid->formatted }}</td>
                <td class="text-right">{{ $fee->getBalance()?->formatted }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-4">
        <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}</p>
    </div>

    <div class="mt-4 text-right">
        <h2>{{ trans('student.fee.authorized_signatory') }}</h2>
    </div>
</x-print.layout>
