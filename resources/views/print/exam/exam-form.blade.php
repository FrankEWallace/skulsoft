<x-print.layout type="{{ Arr::get($layout, 'column', 1) == 1 ? 'centered' : 'full-page' }}" :spacing="false">
    <div style="margin-top: 20px; page-break-after: always;">
        <div style="display: flex; justify-content: space-between;">
            <div style="margin-bottom:10px; width: {{ Arr::get($layout, 'box_width') }}; border: 1px solid black;">

                <div class="{{ Arr::get($layout, 'watermark') ? 'watermark-container' : '' }}">
                    @if (Arr::get($layout, 'watermark'))
                        <img class="watermark-image" src="{{ url(config('config.assets.logo')) }}">
                    @endif

                    <h2 class="heading">{{ trans('exam.form.form') }}</h2>

                    <div style="padding: 10px 15px;">
                        <table class="mt-2" width="100%">
                            <tr>
                                <td valign="top">
                                    <table width="90%" cellspacing="4" cellpadding="0">
                                        <tr>
                                            <td class="font-weight-bold">{{ trans('student.props.name') }}
                                            </td>
                                            <td class="text-right">{{ $student->name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">
                                                {{ trans('student.roll_number.roll_number') }}</td>
                                            <td class="text-right">{{ $student->roll_number }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">
                                                {{ trans('contact.props.father_name') }}</td>
                                            <td class="text-right">{{ $student->father_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">
                                                {{ trans('academic.course.course') }}
                                            </td>
                                            <td class="text-right">
                                                {{ $student->course_name . ' ' . $student->batch_name }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td valign="top">
                                    <table class="table" width="100%" border="1" cellspacing="4" cellpadding="0">
                                        @foreach ($records as $record)
                                            <tr>
                                                @if (Arr::get($layout, 'show_sno'))
                                                    <td>{{ $loop->index + 1 }}</td>
                                                @endif
                                                <td>{{ Arr::get($record, 'subject.name') }}</td>
                                                <td>{{ Arr::get($record, 'subject.code') }}</td>
                                                <td>{{ Arr::get($record, 'subject.description') }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>

                        @includeFirst(
                            [config('config.print.custom_path') . 'exam.signatory', 'print.exam.signatory'],
                            ['layout' => $layout, 'margin' => 'mt-4']
                        )

                        @if (Arr::get($layout, 'show_print_date_time'))
                            <div class="mt-4">
                                <p>{{ trans('general.printed_at') }}:
                                    {{ \Cal::dateTime(now())->formatted }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-print.layout>
