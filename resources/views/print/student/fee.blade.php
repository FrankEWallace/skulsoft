<x-print.layout type="centered">
    @foreach (Arr::get($data, 'feeGroups') as $feeGroup)
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>{{ Arr::get($feeGroup, 'name') }}</th>
                    <th></th>
                    <th></th>
                    <th>{{ Arr::get($feeGroup, 'total')->formatted }}</th>
                    <th>{{ Arr::get($feeGroup, 'paid')->formatted }}</th>
                    <th>{{ Arr::get($feeGroup, 'balance')->formatted }}</th>
                </tr>
            </thead>

            {{-- <tbody>

                @foreach ($feeGroup['fees'] as $fee)
                    <tr>
                        <td>{{ $fee['title'] }}</td>
                        <td>{{ $fee['due_date']->formatted }}</td>
                        <td>
                            @foreach ($fee['records'] as $feeRecord)
                                <p>{{ $feeRecord['head']['name'] }} {{ $feeRecord['amount']->formatted }}</p>
                            @endforeach
                    </tr>
                @endforeach

            </tbody> --}}
        </table>
    @endforeach
</x-print.layout>
