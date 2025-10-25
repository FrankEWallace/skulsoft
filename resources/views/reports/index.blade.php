<x-site.layout>
    <div class="container">
        @include('reports.header')

        <div class="flex justify-between">
            <h2 class="text-2xl text-gray-800 font-semibold">Report</h2>
            <a href="{{ route('app') }}"><i class="fas fa-arrow-left"></i> Go to Application</a>
        </div>

        <ul class="mt-4 list-none space-y-2">
            <li>
                <x-form.button as="link" href="{{ route('reports.student') }}">Student Report</x-form.button>
            </li>
            <li>
                <x-form.button as="link" href="{{ route('reports.finance') }}">Finance Report</x-form.button>
            </li>
            <li>
                <x-form.button as="link" href="{{ route('reports.exam') }}">Exam Report</x-form.button>
            </li>
        </ul>
    </div>
</x-site.layout>
