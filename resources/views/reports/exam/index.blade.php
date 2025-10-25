<x-site.layout>
    <div class="container">
        @include('reports.header')

        <div class="mt-4">
            <h2 class="text-2xl text-gray-800 font-semibold">Exam Report</h2>
        </div>

        <ul class="mt-4 list-none space-y-2">
            <li>
                <x-form.button as="link" href="{{ route('reports.exam.mark') }}">Mark
                    Report</x-form.button>
            </li>
        </ul>

        <div class="mt-4">
            <a href="{{ route('reports.index') }}"><i class="fas fa-arrow-left"></i> Go to Report</a>
        </div>
    </div>
</x-site.layout>
