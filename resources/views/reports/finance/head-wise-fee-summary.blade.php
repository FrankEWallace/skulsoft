<x-site.layout>
    <div class="container">
        @include('reports.header')

        <div class="mt-4">
            <h2 class="text-2xl text-gray-800 font-semibold">Head wise Fee Summary Report</h2>
        </div>

        <form method="GET" action="/head-wise-fee-summary">
            <div class="space-y-2">
                <x-form.group label="Start">
                    <x-form.input type="number" name="start" />
                </x-form.group>
                <x-form.group label="Limit">
                    <x-form.input type="number" name="limit" />
                </x-form.group>
                <x-form.button>Get Result</x-form.button>
            </div>
        </form>

        <div class="mt-4">
            <a href="{{ route('reports.index') }}"><i class="fas fa-arrow-left"></i> Go to Report</a>
        </div>
    </div>
</x-site.layout>
