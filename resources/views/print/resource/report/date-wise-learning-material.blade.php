<x-print.layout type="full-page" :spacing="false">

    <div style="margin: 20px;">
        <div style="width: 100%; margin: 0 auto;">
            <div class="{{ Arr::get($layout, 'watermark') ? 'watermark-container' : '' }}">
                @if (Arr::get($layout, 'watermark'))
                    <img class="watermark-image" src="{{ url(config('config.assets.logo')) }}">
                @endif

                <h2 class="heading">
                    {{ trans('resource.report.date_wise_learning_material.date_wise_learning_material') }}</h2>
                <h3 class="sub-heading">{{ $date }}
            </div>
        </div>

        @foreach ($data as $item)
            <h3 class="sub-heading">Batch: {{ Arr::get($item, 'batch') }}</h3>
            <table border="1" class="border-dark mt-2 table" width="100%" border="0" cellspacing="4"
                cellpadding="0">
                @foreach (Arr::get($item, 'subjects') as $subject)
                    <tr>
                        <td style="width: 20%;">{{ Arr::get($subject, 'name') }}</td>
                        <td style="width: 10%;">{{ Arr::get($subject, 'code') }}</td>
                        <td>
                            @if (count(Arr::get($subject, 'employees')) > 0)
                                @foreach (Arr::get($subject, 'employees') as $employee)
                                    <div>
                                        {{ Arr::get($employee, 'name') }}
                                        <span style="margin-left: 10px;"
                                            class="font-90pc">{{ Arr::get($employee, 'created_at') }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div style="color: red;">
                                    {{ Arr::get($subject, 'incharge') }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endforeach

        @if (Arr::get($layout, 'show_print_date_time'))
            <div class="mt-4">
                <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}</p>
            </div>
        @endif
    </div>
    </div>
    </div>
</x-print.layout>
