<x-print.layout type="full-page">

    <div class="{{ Arr::get($layout, 'watermark') ? 'watermark-container' : '' }}">
        @if (Arr::get($layout, 'watermark'))
            <img class="watermark-image" src="{{ url(config('config.assets.logo')) }}">
        @endif

        @includeFirst([config('config.print.custom_path') . 'exam.report.header', 'print.exam.report.header'])

        <table width="100%" border="0" cellspacing="4" cellpadding="0">
            <tr>
                <td colspan="2">
                    @foreach ($titles as $title)
                        @if (Arr::get($title, 'label'))
                            <div class="{{ Arr::get($title, 'class') }}">{{ Arr::get($title, 'label') }}</div>
                        @endif
                    @endforeach
                </td>
            </tr>
        </table>

        <table border="1" class="border-dark mt-2 table" width="100%" border="0" cellspacing="4"
            cellpadding="0">
            <tr>
                @foreach ($header as $item)
                    <td class="{{ Arr::get($item, 'class') }}" rowspan="{{ Arr::get($item, 'rowspan', 1) }}"
                        colspan="{{ Arr::get($item, 'colspan', 1) }}"
                        @if (Arr::get($item, 'height')) height="{{ Arr::get($item, 'height') }}px" @endif>
                        {{ Arr::get($item, 'label') }}
                    </td>
                @endforeach
            </tr>

            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $item)
                        <td class="{{ Arr::get($item, 'class') }}" rowspan="{{ Arr::get($item, 'rowspan', 1) }}"
                            colspan="{{ Arr::get($item, 'colspan', 1) }}"
                            @if (Arr::get($item, 'height')) height="{{ Arr::get($item, 'height') }}px" @endif>
                            <span class="{{ Arr::get($item, 'text-style') }}">{{ Arr::get($item, 'label') }}</span>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>

        @if (Arr::get($layout, 'show_print_date_time'))
            <div class="mt-4">
                <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}</p>
            </div>
        @endif

        @includeFirst(['print.exam.signatory'], ['layout' => $layout])
    </div>
</x-print.layout>
