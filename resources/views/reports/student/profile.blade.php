<x-print.layout type="full-page">

    <h2 class="heading">Student Profile (Missing)</h2>

    <table border="1" class="border-dark mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
        <thead>
            <th>{{ trans('academic.batch.batch') }}</th>
            <th>{{ trans('academic.batch_incharge.batch_incharge') }}</th>
            <th>Total</th>
            <th>Missing Email</th>
            <th>Missing Alternate Number</th>
            <th>Missing Photo</th>
            <th>Missing Caste</th>
            <th>Missing Category</th>
            <th>Missing Religion</th>
            <th>Missing {{ config('config.student.unique_id_number1_label') }}</th>
            <th>Missing {{ config('config.student.unique_id_number2_label') }}</th>
            <th>Missing {{ config('config.student.unique_id_number3_label') }}</th>
            <th>Missing {{ config('config.student.unique_id_number4_label') }}</th>
            <th>Missing {{ config('config.student.unique_id_number5_label') }}</th>
        </thead>
        @foreach ($data as $item)
            <tr>
                <td>{{ Arr::get($item, 'batch') }}</td>
                <td>{{ Arr::get($item, 'incharge') }}</td>
                <td>{{ Arr::get($item, 'total') }}</td>
                <td>{{ Arr::get($item, 'missing_email') }}</td>
                <td>{{ Arr::get($item, 'missing_alternate_number') }}</td>
                <td>{{ Arr::get($item, 'missing_photo') }}</td>
                <td>{{ Arr::get($item, 'missing_caste') }}</td>
                <td>{{ Arr::get($item, 'missing_category') }}</td>
                <td>{{ Arr::get($item, 'missing_religion') }}</td>
                <td>{{ Arr::get($item, 'missing_unique_id_number1') }}</td>
                <td>{{ Arr::get($item, 'missing_unique_id_number2') }}</td>
                <td>{{ Arr::get($item, 'missing_unique_id_number3') }}</td>
                <td>{{ Arr::get($item, 'missing_unique_id_number4') }}</td>
                <td>{{ Arr::get($item, 'missing_unique_id_number5') }}</td>
            </tr>
        @endforeach
    </table>
</x-print.layout>
