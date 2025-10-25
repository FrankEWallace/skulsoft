<x-print.layout type="{{ Arr::get($meta, 'layout.type') }}">
    <x-print.wrapper>
        <table class="table">
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $item)
                            <td>
                                @if (is_array($item) && array_key_exists('label', $item))
                                    <div>{{ Arr::get($item, 'label') }}</div>
                                    <span class="font-90pc block">{{ Arr::get($item, 'sub_label') }}</span>
                                @elseif(is_array($item))
                                    @foreach ($item as $rowItem)
                                        <div>{{ $rowItem }}</div>
                                    @endforeach
                                @else
                                    {{ $item }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            @if ($footer)
                <tfoot>
                    <tr>
                        @foreach ($footers as $footer)
                            <th>{{ $footer }}</th>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </x-print.wrapper>
</x-print.layout>
