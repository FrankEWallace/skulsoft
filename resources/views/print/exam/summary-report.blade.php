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
                    @foreach ($summaryHeader as $header)
                        <th>
                            {{ Arr::get($header, 'label') }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>

                @foreach ($summaryRows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        @foreach ($row as $item)
                            <td>
                                {{ Arr::get($item, 'label') }}
                            </td>
                        @endforeach
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
