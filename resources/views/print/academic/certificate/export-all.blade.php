<x-print.layout type="full-page">

    @foreach ($data as $item)
        <div style="page-break-after: always;">
            {!! $item !!}
        </div>
    @endforeach

</x-print.layout>
