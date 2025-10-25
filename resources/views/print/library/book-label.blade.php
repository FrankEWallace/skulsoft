<x-print.layout type="centered">
    @foreach (collect($bookCopies)->chunk($labelPerPage) as $bookCopy)
        <div style="page-break-after: always;">
            @foreach ($bookCopy->chunk($column) as $book)
                <div style="margin-top: 5px; display: flex; justify-content: space-between; gap: 5px;">
                    @foreach ($book as $item)
                        <div>
                            <img style="width: 100px; height: 100px;" src="{{ $item['qr_code'] }}" alt="QR Code">
                            <div class="text-center">{{ Arr::get($item, 'title') }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</x-print.layout>
