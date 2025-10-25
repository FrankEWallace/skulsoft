<x-print.layout>

    <div class="{{ Arr::get($layout, 'watermark') ? 'watermark-container' : '' }}">
        @if (Arr::get($layout, 'watermark'))
            <img class="watermark-image" src="{{ url(config('config.assets.logo')) }}">
        @endif

        <div style="padding: 10px 15px;">
            <table width="100%" border="0">
                <tr>
                    <td colspan="2">
                        @foreach ($titles as $title)
                            @if (Arr::get($title, 'label'))
                                <div class="{{ Arr::get($title, 'class') }}">
                                    {{ Arr::get($title, 'label') }}
                                </div>
                            @endif
                        @endforeach
                    </td>
                </tr>
            </table>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ trans('student.admission.props.code_number') }}</th>
                    <th>{{ trans('student.props.name') }}</th>
                    <th>{{ trans('exam.result') }}</th>
                    <th>{{ trans('exam.marksheet.obtained_credit') }}</th>
                    <th>{{ trans('exam.gpa') }}</th>
                </tr>
            </thead>
            <tbody>

                @foreach ($students as $student)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $student->code_number }}</td>
                        <td>{{ $student->name }}</td>
                        <td>{{ Arr::get($student->summary, 'result') }}</td>
                        <td>{{ Arr::get($student->summary, 'total_obtained_credit') }}</td>
                        <td>{{ Arr::get($student->summary, 'gpa') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (Arr::get($layout, 'show_print_date_time'))
        <div class="mt-4" style="padding-left: 10px;">
            <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}
            </p>
        </div>
    @endif
</x-print.layout>
