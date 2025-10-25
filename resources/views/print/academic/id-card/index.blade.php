<x-print.layout type="centered">
    @foreach (collect($data)->chunk($cardPerPage) as $card)
        <div style="page-break-after: always;">
            @foreach ($card->chunk($column) as $student)
                <div style="margin-top: 5px; display: flex; justify-content: space-between; gap: 5px;">
                    @foreach ($student as $item)
                        {!! $item !!}
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</x-print.layout>
