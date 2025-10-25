<x-print.layout type="{{ Arr::get($layout, 'column', 1) == 1 ? 'centered' : 'full-page' }}" :spacing="false">
    @foreach ($students->chunk(Arr::get($layout, 'column', 1)) as $studentPair)
        <div style="margin-top: {{ Arr::get($layout, 'margin_top', 0) }}mm; page-break-after: always;">
            <div style="display: flex; justify-content: space-between;">
                @foreach ($studentPair as $student)
                    <div style="width: {{ Arr::get($layout, 'box_width') }}; border: 0px solid black;">

                        <div class="{{ Arr::get($layout, 'watermark') ? 'watermark-container' : '' }}">
                            @if (Arr::get($layout, 'watermark'))
                                <img class="watermark-image" src="{{ url(config('config.assets.logo')) }}">
                            @endif

                            @includeFirst([
                                config('config.print.custom_path') . 'exam.marksheet.header',
                                'print.exam.marksheet.header',
                            ])

                            @if (view()->exists(config('config.print.custom_path') . 'exam.marksheet.sub-header'))
                                @include(config('config.print.custom_path') . 'exam.marksheet.sub-header')
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

                            <table class="mt-4 outer-table cellpadding" width="100%">
                                <tr>
                                    <td class="font-weight-bold">{{ trans('student.props.name') }}</td>
                                    <td>{{ $student->name }}</td>
                                    <td class="font-weight-bold">{{ trans('contact.props.gender') }}</td>
                                    <td>{{ ucfirst($student->gender) }}</td>
                                    <td class="font-weight-bold">{{ trans('student.admission.props.code_number') }}
                                    </td>
                                    <td>{{ $student->code_number }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">{{ trans('academic.course.course') }}</td>
                                    <td>{{ $student->course_name . ' ' . $student->batch_name }}
                                    </td>
                                    <td class="font-weight-bold">{{ trans('student.roll_number.roll_number') }}</td>
                                    <td>{{ $student->roll_number }}</td>
                                    <td class="font-weight-bold">Class Teacher</td>
                                    <td>{{ $batchIncharges }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Rank</td>
                                    <td>{{ Arr::get($student->summary, 'stats.rank') }} / {{ $totalStudents }}</td>
                                    <td class="font-weight-bold">Best Score</td>
                                    <td>{{ Arr::get($student->summary, 'stats.highest_score') }}</td>
                                    <td class="font-weight-bold">Lowest Score</td>
                                    <td>{{ Arr::get($student->summary, 'stats.lowest_score') }}</td>
                                </tr>
                            </table>

                            <table class="mt-4 outer-table cellpadding" width="100%">
                                @foreach ($student->marks as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td colspan="{{ Arr::get($cell, 'colspan', 1) }}"
                                                rowspan="{{ Arr::get($cell, 'rowspan', 1) }}"
                                                @if (Arr::get($cell, 'type') == 'heading' || Arr::get($cell, 'type') == 'footer') class="text-center font-weight-bold" @endif
                                                @if (in_array(Arr::get($cell, 'key'), ['max_mark', 'obtained_mark', 'obtained_grade', 'rank', 'comment']) ||
                                                        in_array(Arr::get($cell, 'type'), ['summary', 'marks'])) class="text-center" @endif>
                                                @if (Arr::get($cell, 'key') == 'rank' && Arr::get($cell, 'type') == 'footer')
                                                    {{ Arr::get($student->summary, 'stats.rank') }}
                                                @else
                                                    {{ Arr::get($cell, 'label') }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>

                            @if ($student->grading_marks)
                                <table class="mt-4 outer-table cellpadding" width="100%">
                                    @foreach ($student->grading_marks as $row)
                                        <tr>
                                            @foreach ($row as $cell)
                                                <td colspan="{{ Arr::get($cell, 'colspan', 1) }}"
                                                    rowspan="{{ Arr::get($cell, 'rowspan', 1) }}"
                                                    @if (Arr::get($cell, 'type') == 'heading' || Arr::get($cell, 'type') == 'footer') class="text-center font-weight-bold" @endif
                                                    @if (in_array(Arr::get($cell, 'key'), ['max_mark', 'obtained_mark', 'obtained_grade', 'rank']) ||
                                                            in_array(Arr::get($cell, 'type'), ['summary', 'marks'])) class="text-center" @endif>
                                                    {{ Arr::get($cell, 'label') }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            <div class="mt-4" style="display: flex; gap: 10px;">
                                @if ($student->observation_marks)
                                    <div style="flex: 70%;">
                                        <table class="outer-table cellpadding" width="100%"
                                            style="margin-bottom: 10px;">
                                            <tr>
                                                <td>Attendance</td>
                                                <td>{{ Arr::get($student->summary, 'attendance.present') }} /
                                                    {{ Arr::get($student->summary, 'attendance.working_days') }}
                                                </td>
                                                <td>{{ Arr::get($student->summary, 'attendance.present_percentage') }}%
                                                </td>
                                            </tr>
                                        </table>

                                        <table class="outer-table cellpadding" width="100%">
                                            @foreach ($student->observation_marks as $row)
                                                <tr>
                                                    @foreach ($row as $cell)
                                                        <td colspan="{{ Arr::get($cell, 'colspan', 1) }}"
                                                            rowspan="{{ Arr::get($cell, 'rowspan', 1) }}"
                                                            @if (Arr::get($cell, 'type') == 'heading' || Arr::get($cell, 'type') == 'footer') class="text-center font-weight-bold" @endif
                                                            @if (in_array(Arr::get($cell, 'key'), ['max_mark', 'obtained_mark', 'obtained_grade']) ||
                                                                    in_array(Arr::get($cell, 'type'), ['summary', 'marks'])) class="text-center" @endif>
                                                            {{ Arr::get($cell, 'label') }}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                @endif
                                <div style="flex: 30%;" valign="top">
                                    <table class="outer-table cellpadding">
                                        <tr>
                                            <td colspan="2" style="border-left:1px solid black;">
                                                <div class="sub-heading">{{ trans('exam.grade.grade') }}</div>
                                            </td>
                                        </tr>
                                        @foreach ($grade->records as $record)
                                            <tr>
                                                <td style="border-left:1px solid black;">
                                                    {{ Arr::get($record, 'min_score') }} -
                                                    {{ Arr::get($record, 'max_score') }}</td>
                                                <td style="border-left:1px solid black;">
                                                    {{ Arr::get($record, 'code') }}</td>
                                        @endforeach
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <table class="mt-4 outer-table cellpadding" width="100%">
                                <tr>
                                    <td class="font-weight-bold">Result</td>
                                    <td>{{ Arr::get($student->summary, 'comment.result') }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Class Teacher's Remark</td>
                                    <td>{{ Arr::get($student->summary, 'comment.comment') }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Principal's Remark</td>
                                    <td>{{ Arr::get($student->summary, 'comment.incharge_comment') }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Notice</td>
                                    <td>{{ Arr::get($student->summary, 'info') }}</td>
                                </tr>
                            </table>

                            @includeFirst(['print.exam.signatory'], ['layout' => $layout, 'margin' => 'mt-32'])

                            <div class="mt-4"
                                style="padding-left: 10px; padding-top: 10px; border-top: 1px solid gray; display: flex; justify-content: space-between;">
                                <div>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}
                                </div>
                                <div>
                                    {{ config('config.system.footer_credit') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</x-print.layout>
