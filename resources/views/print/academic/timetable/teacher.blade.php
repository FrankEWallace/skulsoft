<x-print.layout>
    @includeFirst([config('config.print.custom_path') . 'header', 'print.header'])

    <h2 class="heading">
        {{ trans('academic.timetable.teacher_timetable') }}
    </h2>

    <div class="mt-4 sub-heading">
        {{ $employee->name . ' (' . $employee->code_number . ')' }}
    </div>

    <div class="mt-1 sub-heading">
        {{ $employee->designation_name }}
    </div>

    <p>{{ trans('general.errors.feature_under_development') }}</p>

    {{-- <table class="mt-4 table cellpadding" width="100%">
        @foreach ($days as $day)
            <tr>
                <td>{{ Arr::get($day, 'day.label') }}</td>
                @foreach (Arr::get($day, 'sessions') as $session)
                    <td>
                        {{ Arr::get($session, 'name') }}

                        <div class="font-90pc">
                            {{ Arr::get($session, 'start_time')->formatted }} -
                            {{ Arr::get($session, 'end_time')->formatted }}
                        </div>

                        <div class="font-90pc">
                            {{ Arr::get($session, 'subject.name') }}
                        </div>

                        <div class="font-90pc">
                            {{ Arr::get($session, 'room') }}
                        </div>

                        <div class="font-90pc">
                            {{ Arr::get($session, 'batch') }}
                        </div>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table> --}}
</x-print.layout>
