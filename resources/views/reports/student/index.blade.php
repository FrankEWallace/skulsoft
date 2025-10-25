<x-site.layout>
    <div class="container">
        @include('reports.header')

        <div class="mt-4">
            <h2 class="text-2xl text-gray-800 font-semibold">Student Report</h2>
        </div>

        <ul class="mt-4 list-none space-y-2">
            <li>
                <x-form.button as="link" href="{{ route('reports.student.profile') }}">Profile Report</x-form.button>
            </li>
            <li>
                <x-form.button as="link" href="{{ route('reports.student.attendance') }}">Attendance
                    Report</x-form.button>
            </li>
            <li>
                <x-form.button as="link" href="{{ route('reports.student.sibling') }}">Sibling Report</x-form.button>
            </li>
        </ul>

        <div class="mt-4">
            <a href="{{ route('reports.index') }}"><i class="fas fa-arrow-left"></i> Go to Report</a>
        </div>
    </div>
</x-site.layout>
