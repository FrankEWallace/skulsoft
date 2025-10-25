@if ($recordMismatches->count())
    <h3 style="margin-top: 20px;">Transaction Record Mismatches ({{ $recordMismatches->count() }})</h3>

    <table style="margin-top: 20px;">
        <tr>
            <th>Transaction ID</th>
            <th>Code Number</th>
            <th>Amount</th>
            <th>Sum Amount</th>
            <th>Cancelled At</th>
        </tr>
        @foreach ($recordMismatches as $transaction)
            <tr>
                <td>{{ $transaction->id }}</td>
                <td>{{ $transaction->code_number }}</td>
                <td>{{ $transaction->amount->value }}</td>
                <td>{{ round($transaction->sum_amount, 2) }}</td>
                <td>{{ $transaction->cancelled_at->formatted }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if ($studentFeePaymentMismatches->count())
    <h3 style="margin-top: 20px;">Fee Payment Mismatches ({{ $studentFeePaymentMismatches->count() }})</h3>

    <table style="margin-top: 20px;">
        <tr>
            <th>Transaction ID</th>
            <th>Code Number</th>
            <th>Amount</th>
            <th>Sum Amount</th>
            <th>Cancelled At</th>
        </tr>
        @foreach ($studentFeePaymentMismatches as $transaction)
            <tr>
                <td>{{ $transaction->id }}</td>
                <td>{{ $transaction->code_number }}</td>
                <td>{{ $transaction->amount->value }}</td>
                <td>{{ round($transaction->sum_amount, 2) }}</td>
                <td>{{ $transaction->cancelled_at->formatted }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if ($transactionPaymentMismatches->count())
    <h3 style="margin-top: 20px;">Transaction Payment Mismatches ({{ $transactionPaymentMismatches->count() }})</h3>

    <table style="margin-top: 20px;">
        <tr>
            <th>Transaction ID</th>
            <th>Code Number</th>
            <th>Amount</th>
            <th>Sum Amount</th>
            <th>Cancelled At</th>
        </tr>
        @foreach ($transactionPaymentMismatches as $transaction)
            <tr>
                <td>{{ $transaction->id }}</td>
                <td>{{ $transaction->code_number }}</td>
                <td>{{ $transaction->amount->value }}</td>
                <td>{{ round($transaction->sum_amount, 2) }}</td>
                <td>{{ $transaction->cancelled_at->formatted }}</td>
            </tr>
        @endforeach
    </table>
@endif
