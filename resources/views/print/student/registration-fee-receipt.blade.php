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
                    {{ trans('student.registration.receipt') }}
                    @if ($transaction->cancelled_at->value)
                        <span style="color: red;">({{ trans('general.cancelled') }})</span>
                    @endif
                    @if ($transaction->rejected_at->value)
                        <span style="color: orange;">({{ trans('general.rejected') }})</span>
                    @endif
                </h2>
                <p class="text-center">
                    {{ $registration->period->name }}
                </p>
            </td>
        </tr>
    </table>
    <table class="mt-2" width="100%" border="0" cellspacing="4" cellpadding="0">
        <tr>
            <td width="50%" valign="top">
                <div class="sub-heading-left">{{ trans('finance.transaction.props.code_number') }}:
                    {{ $transaction->code_number }}</div>
            </td>
            <td width="50%" valign="top">
                <div class="sub-heading text-right">{{ trans('finance.transaction.props.date') }}:
                    {{ $transaction->date->formatted }}</div>
            </td>
        </tr>
    </table>
    <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
        <tr>
            <th>{{ trans('student.props.name') }}</th>
            <td class="text-right">{{ $registration->contact->name }}</td>
            <th>{{ trans('student.registration.props.code_number') }}</th>
            <td class="text-right">{{ $registration->code_number }}</td>
        </tr>
        <tr>
            <th>{{ trans('contact.props.father_name') }}</th>
            <td class="text-right">{{ $registration->contact->father_name }}</td>
            <th>{{ trans('contact.props.contact_number') }}</th>
            <td class="text-right">{{ $registration->contact->contact_number }}</td>
        </tr>
        <tr>
            <th>{{ trans('academic.course.course') }}</th>
            <td class="text-right">
                {{ $registration->course->name }} <br />
                <span class="font-90pc"></span>
            </td>
            <th>{{ trans('contact.props.birth_date') }}</th>
            <td class="text-right">{{ $registration->contact->birth_date->formatted }}</td>
        </tr>
    </table>

    <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
        <thead>
            <tr>
                <th>
                    <div>
                        {{ trans('finance.fee_head.fee_head') }}
                    </div>
                </th>
                <th>
                    <div class="text-right">{{ trans('finance.transaction.props.amount') }}</div>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div>
                        {{ trans('student.registration.fee') }}
                    </div>
                </td>
                <td>
                    <div class="text-right">
                        {{ $transaction->amount->formatted }}
                    </div>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th>{{ trans('finance.fee.total') }}</th>
                <th>
                    <div class="text-right">{{ $transaction->amount->formatted }}</div>
                </th>
            </tr>
        </tfoot>
    </table>

    <div class="mt-4">
        @foreach ($transaction->payments as $payment)
            <div>
                <strong>{{ trans('finance.payment_method.payment_method') }}</strong>:
                {{ $payment->method->name }}
                {{ $payment->amount->formatted }}
            </div>
            <div class="font-90pc mt-1">
                @if ($payment->getDetail('reference_number'))
                    {{ trans('finance.transaction.props.reference_number') }}:
                    {{ $payment->getDetail('reference_number') }}
                @endif
                @if ($payment->getDetail('instrument_number'))
                    {{ trans('finance.transaction.props.instrument_number') }}:
                    {{ $payment->getDetail('instrument_number') }}
                @endif
                @if ($payment->getDetail('instrument_date'))
                    {{ trans('finance.transaction.props.instrument_date') }}:
                    {{ \Cal::date($payment->getDetail('instrument_date'))->formatted }}
                @endif
                @if ($payment->getDetail('clearing_date'))
                    {{ trans('finance.transaction.props.clearing_date') }}:
                    {{ \Cal::date($payment->getDetail('clearing_date'))->formatted }}
                @endif
                @if ($payment->getDetail('bank_detail'))
                    {{ trans('finance.transaction.props.bank_detail') }}:
                    {{ $payment->getDetail('bank_detail') }}
                @endif
            </div>
        @endforeach
    </div>

    @if ($transaction->cancelled_at->value && $transaction->cancellation_remarks)
        <div class="mt-4">
            <p style="color: red;">{{ trans('finance.transaction.props.cancellation_remarks') }}:
                {{ $transaction->cancellation_remarks }}</p>
        </div>
    @endif

    @if ($transaction->rejected_at->value && $transaction->rejection_remarks)
        <div class="mt-4">
            <p style="color: red;">{{ trans('finance.transaction.props.rejection_remarks') }}:
                {{ $transaction->rejection_remarks }}</p>
        </div>
    @endif

    @if ($transaction->is_online)
        <div class="mt-4 text-center">
            <p>{{ trans('finance.online_receipt_info') }}</p>
        </div>
    @else
        <div class="mt-4 text-right">
            <h2>{{ trans('student.fee.authorized_signatory') }}</h2>
        </div>
    @endif

    <div class="mt-4">
        <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}</p>
    </div>
</x-print.layout>
