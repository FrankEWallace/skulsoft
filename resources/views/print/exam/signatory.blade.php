<table class="{{ $margin ?? 'mt-12' }}" style="padding-left: 10px; padding-right: 10px;" width="100%">
    <tr>
        @if (Arr::get($layout, 'signatory1'))
            <td>{{ Arr::get($layout, 'signatory1') }}</td>
        @endif

        @if (Arr::get($layout, 'signatory2'))
            @if (Arr::get($layout, 'signatory3'))
                <td class="text-center">{{ Arr::get($layout, 'signatory2') }}</td>
            @else
                <td class="text-right">{{ Arr::get($layout, 'signatory2') }}</td>
            @endif
        @endif

        @if (Arr::get($layout, 'signatory3'))
            @if (Arr::get($layout, 'signatory4'))
                <td class="text-center">{{ Arr::get($layout, 'signatory3') }}</td>
            @else
                <td class="text-right">{{ Arr::get($layout, 'signatory3') }}</td>
            @endif
        @endif

        @if (Arr::get($layout, 'signatory4'))
            <td class="text-right">{{ Arr::get($layout, 'signatory4') }}</td>
        @endif
    </tr>
</table>
