<x-print.layout type="full-page">
    <x-print.wrapper>

        <h2 class="heading text-center">
            {{ trans('employee.payroll.payment_advice') }}
        </h2>

        <h3 class="sub-heading text-center">
            {{ Arr::get($params, 'start_date')?->formatted }} - {{ Arr::get($params, 'end_date')?->formatted }}
        </h3>

        <div class="mt-4" style="page-break-before: always; max-width: 800px; margin: 0 auto;">
            <h3 class="sub-heading text-center">
                {{ Arr::get($params, 'payment_advice_period') }}
            </h3>

            <div class="mt-4 text-center">
                {{ Arr::get($params, 'payment_advice_bank_details') }}
                {{ Arr::get($params, 'payment_advice_account_details') }}
            </div>

            <table class="mt-4 table cellpadding" width="100%">
                <thead>
                    <tr>
                        <td>#</td>
                        <td>{{ trans('employee.props.name') }}</td>
                        <td>{{ trans('finance.account.props.number') }}</td>
                        <td>{{ trans('finance.account.props.bank_name') }}</td>
                        <td>Bank Code</td>
                        <td>{{ trans('employee.payroll.salary_structure.props.net_salary') }}</td>
                    </tr>
                    </head>
                <tbody>
                    @foreach ($summary as $key => $value)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ Arr::get($value, 'name') }}</td>
                            <td>{{ Arr::get($value, 'account_number') }}</td>
                            <td>{{ Arr::get($value, 'bank_name') }}</td>
                            <td>{{ Arr::get($value, 'bank_code1') }}</td>
                            <td>{{ Arr::get($value, 'net_salary')?->formatted }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">{{ trans('general.total') }}</td>
                        <td>{{ $grandTotal?->formatted }}</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-right">
                            {{ App\Helpers\CurrencyConverter::toWord($grandTotal?->value) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-print.wrapper>
</x-print.layout>
