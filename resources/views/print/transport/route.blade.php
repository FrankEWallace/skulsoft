<x-print.layout type="full-page">
    @includeFirst([config('config.print.custom_path') . 'header', 'print.header'])

    <h2 class="heading">{{ Arr::get($route, 'name') }} <span
            class="font-90pc">({{ Arr::get($route, 'direction.label') }})</span></h2>

    <table width="100%">
        <tr>
            <td><strong>{{ Arr::get($route, 'vehicle.name') }}
                    {{ Arr::get($route, 'vehicle.registration_number') }}</strong></td>
            <td class="text-right">{{ trans('transport.route.props.max_capacity') }}
                <strong>{{ count(Arr::get($route, 'passengers', [])) }} /
                    {{ Arr::get($route, 'max_capacity') }}</strong>
            </td>
        </tr>
    </table>

    @if (in_array('timing', $inclusions))
        <table width="100%">
            <tr>
                @if (Arr::get($route, 'direction.value') == 'arrival' || Arr::get($route, 'direction.value') == 'roundtrip')
                    <td>
                        <h2 class="sub-heading">{{ trans('transport.route.arrival_stoppages') }}</h2>
                        <table class="mt-4 table" style="table-layout: fixed;">
                            @foreach (Arr::get($route, 'arrival_stoppages') as $stoppage)
                                <tr>
                                    <td>{{ Arr::get($stoppage, 'name') }}</td>
                                    <td>{{ Arr::get($stoppage, 'arrival_time.formatted') }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                @endif
                @if (Arr::get($route, 'direction.value') == 'departure' || Arr::get($route, 'direction.value') == 'roundtrip')
                    <td>
                        <h2 class="sub-heading">{{ trans('transport.route.departure_stoppages') }}</h2>
                        <table class="mt-4 table" style="table-layout: fixed;">
                            @foreach (Arr::get($route, 'departure_stoppages') as $stoppage)
                                <tr>
                                    <td>{{ Arr::get($stoppage, 'name') }}</td>
                                    <td>{{ Arr::get($stoppage, 'arrival_time.formatted') }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    @if (in_array('passenger', $inclusions))
        <h2 class="sub-heading">{{ trans('student.student') }}</h2>
        <table class="mt-4 table" style="table-layout: fixed;">
            <thead>
                <tr>
                    <th>{{ trans('general.sno') }}</th>
                    <th>{{ trans('student.props.name') }}</th>
                    <th></th>
                    <th></th>
                    <th>{{ trans('transport.stoppage.stoppage') }}</th>
                    @if (Arr::get($route, 'show_contact_number'))
                        <th>{{ trans('contact.props.contact_number') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach (collect(Arr::get($route, 'passengers'))->filter(fn($passenger) => $passenger['type']['value'] == 'student') as $passenger)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ Arr::get($passenger, 'name') }}</td>
                        <td>{{ Arr::get($passenger, 'detail') }}</td>
                        <td>{{ Arr::get($passenger, 'sub_detail') }}</td>
                        <td>{{ Arr::get($passenger, 'stoppage') }}</td>
                        @if (Arr::get($route, 'show_contact_number'))
                            <td>{{ Arr::get($passenger, 'contact_number') }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2 class="sub-heading">{{ trans('employee.employee') }}</h2>
        <table class="mt-4 table" style="table-layout: fixed;">
            <thead>
                <tr>
                    <th>{{ trans('general.sno') }}</th>
                    <th>{{ trans('employee.props.name') }}</th>
                    <th></th>
                    <th>{{ trans('transport.stoppage.stoppage') }}</th>
                    <th>{{ trans('contact.props.contact_number') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach (collect(Arr::get($route, 'passengers'))->filter(fn($passenger) => $passenger['type']['value'] == 'employee') as $passenger)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ Arr::get($passenger, 'name') }}</td>
                        <td>{{ Arr::get($passenger, 'detail') }}</td>
                        <td>{{ Arr::get($passenger, 'stoppage') }}</td>
                        <td>{{ Arr::get($passenger, 'contact_number') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-print.layout>
