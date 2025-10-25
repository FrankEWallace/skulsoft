<x-print.layout type="{{ Arr::get($layout, 'column', 1) == 1 ? 'centered' : 'full-page' }}" :spacing="false">
    @foreach ($students->chunk(Arr::get($layout, 'column', 1) * 2) as $studentPair)
        <div style="margin-top: 20px; page-break-after: always;">
            @foreach ($studentPair->chunk(Arr::get($layout, 'column', 1)) as $studentList)
                <div style="display: flex; justify-content: space-between;">
                    @foreach ($studentList as $student)
                        <div
                            style="margin-bottom:10px; width: {{ Arr::get($layout, 'box_width') }}; border: 1px solid black;">

                            <div class="{{ Arr::get($layout, 'watermark') ? 'watermark-container' : '' }}">
                                @if (Arr::get($layout, 'watermark'))
                                    <img class="watermark-image" src="{{ url(config('config.assets.logo')) }}">
                                @endif

                                @includeFirst([
                                    config('config.print.custom_path') . 'exam.admit-card.header',
                                    'print.exam.admit-card.header',
                                ])

                                @if (view()->exists(config('config.print.custom_path') . 'exam.admit-card.sub-header'))
                                    @include(config('config.print.custom_path') . 'exam.admit-card.sub-header',
                                        [
                                            'titles' => $titles,
                                        ]
                                    )
                                @else
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
                                @endif


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
                                                <table class="table" width="100%" border="1" cellspacing="4"
                                                    cellpadding="0">
                                                    @foreach ($subjects as $subject)
                                                        @if (empty($student->subjects) || (!empty($student->subjects) && in_array($subject['shortcode'], $student->subjects)))
                                                            <tr>
                                                                @if (Arr::get($layout, 'show_sno'))
                                                                    <td>{{ $loop->index + 1 }}</td>
                                                                @endif
                                                                <td>{{ Arr::get($subject, 'date') }}</td>
                                                                <td>{{ Arr::get($subject, 'name') }}</td>
                                                                <td class="font-85">{{ Arr::get($subject, 'date') }}
                                                                </td>
                                                                <td>{{ Arr::get($subject, 'start_time') }} -
                                                                    {{ Arr::get($subject, 'end_time') }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    @includeFirst(
                                        [
                                            config('config.print.custom_path') . 'exam.signatory',
                                            'print.exam.signatory',
                                        ],
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
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</x-print.layout>
