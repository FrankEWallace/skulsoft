<x-print.layout type="centered">
    @foreach (Arr::get($gatePass, 'audiences', []) as $audience)
        @includeFirst([config('config.print.custom_path') . 'header', 'print.header'])

        <h2 class="heading">{{ trans('reception.gate_pass.gate_pass') }}</h2>

        <table class="table">
            <tr>
                <th>{{ trans('general.sno') }}</th>
                <td>{{ Arr::get($gatePass, 'code_number') }}</td>
                <th>{{ trans('reception.gate_pass.props.datetime') }}</th>
                <td>{{ Arr::get($gatePass, 'start_at.formatted') }}</td>
            </tr>
            <tr>
                <th>{{ trans('reception.gate_pass.props.requester') }}</th>
                <td>
                    {{ Arr::get($audience, 'name') }}
                </td>
                <th>
                    @if (Arr::get($gatePass, 'requester_type.value') == 'employee')
                        {{ trans('employee.props.code_number') }}
                    @elseif(Arr::get($gatePass, 'requester_type.value') == 'student')
                        {{ trans('contact.props.father_name') }}
                    @endif
                </th>
                <td>{{ Arr::get($audience, 'detail') }}</td>
            </tr>
            <tr>
                <th>{{ trans('reception.gate_pass.purpose.purpose') }}</th>
                <td colspan="3">{{ Arr::get($gatePass, 'purpose.name') }}</td>
            </tr>
            <tr>
                <th>{{ trans('reception.gate_pass.props.reason') }}</th>
                <td colspan="3">{{ Arr::get($gatePass, 'reason') }}</td>
            </tr>
        </table>
        <div class="mt-8">
            <p class="text-right">{{ trans('print.authorized_signatory') }}</p>
        </div>
        <div class="mt-4">
            <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}
            </p>
        </div>
    @endforeach
</x-print.layout>
