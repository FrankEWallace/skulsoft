<x-print.layout type="full-page">
    <x-print.wrapper>

        <h2 class="heading text-center">
            {{ trans('employee.payroll.salary_sheet') }}
        </h2>

        <h3 class="sub-heading text-center">
            {{ Arr::get($params, 'start_date')?->formatted }} - {{ Arr::get($params, 'end_date')?->formatted }}
        </h3>

        <div class="mx-4">
            <table class="mt-4 outer-table cellpadding" width="100%">
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td colspan="{{ Arr::get($cell, 'colspan', 1) }}"
                                rowspan="{{ Arr::get($cell, 'rowspan', 1) }}"
                                @if (Arr::get($cell, 'type') == 'header' || Arr::get($cell, 'type') == 'footer') class="text-center font-weight-bold" @endif
                                @if (Arr::get($cell, 'key') == 'attendance') class="text-center" @endif
                                @if (in_array(Arr::get($cell, 'key'), [
                                        'total_earning',
                                        'total_deduction',
                                        'net_salary',
                                        'total_employer_contribution',
                                        'total_expense',
                                    ])) class="font-weight-bold text-right" @endif
                                @if (Arr::get($cell, 'data_type') == 'numeric') style="text-align: right !important;" @endif
                                @if (in_array(Arr::get($cell, 'key'), []) || in_array(Arr::get($cell, 'type'), [])) class="text-center" @endif>
                                {{ Arr::get($cell, 'label') }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    </x-print.wrapper>
</x-print.layout>
