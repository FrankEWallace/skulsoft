<x-print.layout type="centered">
    @foreach ($parents as $parent)
        <div>
            <h1 class="font-110pc font-weight-bold">{{ $loop->iteration }}. {{ $parent->father_name }} &
                {{ $parent->mother_name }}</h1>

            <table class="table" width="100%">
                @foreach ($parent->students as $student)
                    <tr>
                        <td width="40%">{{ $student->name }}</td>
                        <td width="10%">{{ $student->admission_number }}</td>
                        <td width="20%">{{ $student->course_name . ' ' . $student->batch_name }}</td>
                        <td width="30%">{{ $student->fee_concession }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</x-print.layout>
