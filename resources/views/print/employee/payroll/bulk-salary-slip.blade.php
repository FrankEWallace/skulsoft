<x-print.layout type="centered">

    @foreach ($payrolls as $payroll)
        <div style="margin-top: 0mm; page-break-after: always;">

            <div class="watermark-container">
                <img class="watermark-image" src="{{ url(config('config.assets.logo')) }}">

                @includeFirst(['print.custom.header', 'print.header'])

                <h2 class="heading text-center">
                    {{ trans('employee.payroll.salary_slip') }}
                    @if ($payroll->cancelled_at?->value)
                        <span style="color: red;">({{ trans('general.cancelled') }})</span>
                    @endif
                </h2>

                <p class="text-center">{{ $payroll->period }} - {{ $payroll->duration }}
                </p>

                <table class="mt-2" width="100%" border="0" cellspacing="4" cellpadding="0">
                    <tr>
                        <td width="50%" valign="top">
                            <div class="sub-heading-left">{{ trans('employee.payroll.props.code_number') }}:
                                {{ $payroll->code_number }}</div>
                        </td>
                        <td width="50%" valign="top">
                            <div class="sub-heading text-right">{{ trans('employee.payroll.props.date') }}:
                                {{ \Cal::date($payroll->created_at)->formatted }}</div>
                        </td>
                    </tr>
                </table>
                <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
                    <tr>
                        <th>{{ trans('employee.props.name') }}</th>
                        <td class="text-right">{{ $payroll->employee->name }}</td>
                        <th>{{ trans('employee.props.code_number') }}</th>
                        <td class="text-right">{{ $payroll->employee->code_number }}</td>
                    </tr>
                    <tr>
                        <th>{{ trans('employee.department.department') }}</th>
                        <td class="text-right">{{ $payroll->employee->department_name }}</td>
                        <th>{{ trans('employee.designation.designation') }}</th>
                        <td class="text-right">{{ $payroll->employee->designation_name }}</td>
                    </tr>
                    <tr>
                        <th>{{ trans('employee.employment_status.employment_status') }}</th>
                        <td class="text-right">{{ $payroll->employee->employment_status_name }}</td>
                        <th>{{ trans('employee.props.joining_date') }}</th>
                        <td class="text-right">{{ $payroll->employee->joining_date->formatted }}</td>
                    </tr>
                    <tr>
                        <th>{{ config('config.employee.unique_id_number1_label') }}</th>
                        <td class="text-right">{{ $payroll->employee->unique_id_number1 }}</td>
                        <th>{{ config('config.employee.unique_id_number2_label') }}</th>
                        <td class="text-right">{{ $payroll->employee->unique_id_number2 }}</td>
                    </tr>
                </table>

                <table class="mt-8 table" width="100%">
                    <thead>
                        <tr>
                            <th colspan="2" class="text-center">
                                {{ trans('employee.payroll.pay_head.categories.earning') }}</th>
                            <th colspan="2" class="text-center">
                                {{ trans('employee.payroll.pay_head.categories.deduction') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="2" valign="top" style="padding: 0px;">
                                <table width="100%">
                                    @foreach ($payroll->records->where('visibility')->where('payHead.category.value', 'earning') as $record)
                                        <tr>
                                            <td>{{ $record->payHead->name }}</td>
                                            <td class="text-right">{{ $record->amount->formatted }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                            <td colspan="2" valign="top" style="padding: 0px;">
                                <table width="100%">
                                    @foreach ($payroll->records->where('visibility')->where('payHead.category.value', 'deduction') as $record)
                                        <tr>
                                            <td>{{ $record->payHead->name }}</td>
                                            <td class="text-right">{{ $record->amount->formatted }}</td>
                                        </tr>
                                    @endforeach

                                    @foreach ($payroll->records->where('visibility')->where('payHead.category.value', 'employee_contribution') as $record)
                                        <tr>
                                            <td>{{ $record->payHead->name }}</td>
                                            <td class="text-right">{{ $record->amount->formatted }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>{{ trans('employee.payroll.salary_structure.props.net_earning') }}</td>
                            <td class="text-right">{{ \Price::from($payroll->getMeta('actual.earning'))?->formatted }}
                            </td>
                            <td>{{ trans('employee.payroll.salary_structure.props.net_deduction') }}</td>
                            <td class="text-right">
                                {{ \Price::from($payroll->getMeta('actual.deduction') + $payroll->getMeta('actual.employee_contribution'))?->formatted }}
                            </td>
                        </tr>
                    </tfoot>
                    <tfoot>
                        <tr>
                            <td>{{ trans('employee.payroll.salary_structure.props.net_salary') }}</td>
                            <td class="text-right">{{ $payroll->total->formatted }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <table class="mt-4 table" width="100%">
                    <thead>
                        <tr>
                            <th colspan="2" class="text-center">
                                {{ trans('employee.payroll.pay_head.categories.employer_contribution') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payroll->records->where('payHead.category.value', 'employer_contribution') as $record)
                            <tr>
                                <td>{{ $record->payHead->name }}</td>
                                <td class="text-right">{{ $record->amount->formatted }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>{{ trans('employee.payroll.salary_structure.props.net_employer_contribution') }}</td>
                            <td class="text-right">
                                {{ \Price::from($payroll->getMeta('actual.employer_contribution'))?->formatted }}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <table class="mt-4 table" width="100%">
                    <thead>
                        <tr>
                            <th colspan="2" class="text-center">
                                {{ trans('global.summary', ['attribute' => trans('attendance.attendance')]) }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payroll->attendanceSummary as $attendance)
                            <tr>
                                <td>{{ Arr::get($attendance, 'name') }} ({{ Arr::get($attendance, 'code') }})</td>
                                <td class="text-right">{{ Arr::get($attendance, 'count') }}
                                    {{ trans('list.durations.' . Arr::get($attendance, 'unit')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if (isset($payroll->account))
                    <div class="mt-4">
                        <p><span>{{ trans('finance.account.props.name') }}:</span> {{ $payroll->account->name }}</p>
                        <p><span>{{ trans('finance.account.props.number') }}:</span> {{ $payroll->account->number }}
                        </p>
                        <p><span>{{ trans('finance.account.props.bank_name') }}:</span>
                            {{ Arr::get($payroll->account->bank_details, 'bank_name') }}</p>
                        <p><span>{{ trans('finance.account.props.branch_name') }}:</span>
                            {{ Arr::get($payroll->account->bank_details, 'bank_branch') }}
                        </p>
                    </div>
                @endif

                <div class="mt-4 text-center">
                    <p>{{ trans('employee.payroll.footer_info') }}</p>
                </div>
                {{-- <div class="mt-4 text-right">
                <h2>{{ trans('employee.payroll.authorized_signatory') }}</h2>
            </div> --}}

                <div class="mt-4">
                    <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}</p>
                </div>
            </div>
        </div>
    @endforeach

</x-print.layout>
