<x-print.layout type="centered">
    @includeFirst([config('config.print.custom_path') . 'header', 'print.header'])

    <h2 class="heading">{{ Arr::get($transaction, 'type.label') }}</h2>

    <table width="100%">
        <tr>
            <td><strong>{{ trans('finance.transaction.props.code_number_short') }}
                    {{ Arr::get($transaction, 'code_number') }}</strong></td>
            <td class="text-right"><strong>{{ trans('finance.transaction.props.date') }}
                    {{ Arr::get($transaction, 'date.formatted') }}</strong></td>
        </tr>
    </table>

    @if (Arr::get($transaction, 'cancelled_at.value') && Arr::get($transaction, 'cancellation_remarks'))
        <div class="mt-4">
            <p style="color: red;">{{ trans('finance.transaction.props.cancellation_remarks') }}:
                {{ Arr::get($transaction, 'cancellation_remarks') }}</p>
        </div>
    @endif

    @if (Arr::get($transaction, 'rejected_at.value') && Arr::get($transaction, 'rejection_remarks'))
        <div class="mt-4">
            <p style="color: red;">{{ trans('finance.transaction.props.rejection_remarks') }}:
                {{ Arr::get($transaction, 'rejection_remarks') }}</p>
        </div>
    @endif

    <table class="mt-4 table" style="table-layout: fixed;">
        <tr>
            <th style="width:25%;">{{ Arr::get($transaction, 'sub_heading') }}</th>
            <td>
                @if (Arr::get($transaction, 'head'))
                    {{ Arr::get($transaction, 'head') }}
                @else
                    {{ Arr::get($transaction, 'category.name') }}
                @endif
            </td>
        </tr>
        <tr>
            <th style="width:25%;">{{ trans('general.detail') }}</th>
            <td>
                @if (Arr::get($transaction, 'head'))
                    {{ Arr::get($transaction, 'transactionable.name') }} </br>
                    <span class="font-90pc">{{ Arr::get($transaction, 'transactionable.contact') }}</span>
                @else
                    {{ Arr::get($transaction, 'record.ledger.name') }}
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ trans('finance.transaction.props.amount') }}</th>
            <td>{{ Arr::get($transaction, 'amount.formatted') }}
                {{ App\Helpers\CurrencyConverter::toWord(Arr::get($transaction, 'amount.value')) }}</td>
        </tr>
        <tr>
            <th>{{ trans('finance.transaction.props.description') }}</th>
            <td>{{ Arr::get($transaction, 'description') ?? Arr::get($transaction, 'remarks') }}</td>
        </tr>
        <tr>
            <th>{{ trans('finance.payment_method.payment_method') }}</th>
            <td>

                @foreach (Arr::get($transaction, 'payments', []) as $payment)
                    <div>
                        {{ Arr::get($payment, 'method_name') }}
                        {{ Arr::get($payment, 'amount.formatted') }}
                    </div>
                    <div class="font-90pc mt-1">
                        @if (Arr::get($payment, 'details.reference_number'))
                            {{ trans('finance.transaction.props.reference_number') }}:
                            {{ Arr::get($payment, 'details.reference_number') }}
                        @endif
                        @if (Arr::get($payment, 'details.instrument_number'))
                            {{ trans('finance.transaction.props.instrument_number') }}:
                            {{ Arr::get($payment, 'details.instrument_number') }}
                        @endif
                        @if (Arr::get($payment, 'details.instrument_date'))
                            {{ trans('finance.transaction.props.instrument_date') }}:
                            {{ \Cal::date(Arr::get($payment, 'details.instrument_date'))->formatted }}
                        @endif
                        @if (Arr::get($payment, 'details.clearing_date'))
                            {{ trans('finance.transaction.props.clearing_date') }}:
                            {{ \Cal::date(Arr::get($payment, 'details.clearing_date'))->formatted }}
                        @endif
                        @if (Arr::get($payment, 'details.bank_detail'))
                            {{ trans('finance.transaction.props.bank_detail') }}:
                            {{ Arr::get($payment, 'details.bank_detail') }}
                        @endif
                    </div>
                @endforeach
            </td>
        </tr>
    </table>

    <div class="mt-4">
        <p class="font-80pc">{{ trans('user.user') }}: {{ Arr::get($transaction, 'user.profile.name') }}</p>
    </div>
    <div class="mt-8">
        <p class="text-right">{{ trans('print.authorized_signatory') }}</p>
    </div>
    <div class="mt-4">
        <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}
        </p>
    </div>
</x-print.layout>
