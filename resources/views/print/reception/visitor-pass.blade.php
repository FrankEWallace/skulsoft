<x-print.layout type="centered">
    @includeFirst([config('config.print.custom_path') . 'header', 'print.header'])

    <h2 class="heading">{{ trans('reception.visitor_log.pass') }}</h2>

    <table class="table">
        <tr>
            <th>{{ trans('general.sno') }}</th>
            <td>{{ Arr::get($visitorLog, 'code_number') }}</td>
            <th>{{ trans('reception.visitor_log.props.entry') }}</th>
            <td>{{ Arr::get($visitorLog, 'entry_at.formatted') }}</td>
        </tr>
        <tr>
            <th>{{ trans('reception.visitor_log.props.name') }}</th>
            <td>
                {{ Arr::get($visitorLog, 'name') }}
                @if (Arr::get($visitorLog, 'type.value') != 'other')
                    ({{ Arr::get($visitorLog, 'type.label') }})
                @endif
            </td>
            <th>{{ trans('reception.visitor_log.props.contact_number') }}</th>
            <td>{{ Arr::get($visitorLog, 'contact_number') }}</td>
        </tr>
        <tr>
            <th>{{ trans('reception.visitor_log.purpose.purpose') }}</th>
            <td>{{ Arr::get($visitorLog, 'purpose.name') }}</td>
            <th>{{ trans('reception.visitor_log.props.count') }}</th>
            <td>{{ Arr::get($visitorLog, 'count') }}</td>
        </tr>
        <tr>
            <th>{{ trans('reception.visitor_log.props.whom_to_meet') }}</th>
            <td colspan="3">{{ Arr::get($visitorLog, 'employee.name') }}
                ({{ Arr::get($visitorLog, 'employee.designation') }})</td>
        </tr>
    </table>
    <div class="mt-8">
        <p class="text-right">{{ trans('print.authorized_signatory') }}</p>
    </div>
    <div class="mt-4">
        <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}
        </p>
    </div>
</x-print.layout>
