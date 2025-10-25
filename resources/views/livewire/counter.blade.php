<div class="px-10 py-4">
    <div class="flex items-center gap-4">
        <x-form.button wire:click="increment">Increment</x-form.button>
        <div class="my-4 text-2xl">{{ $count }}</div>
        <x-form.button wire:click="decrement">Decrement</x-form.button>
    </div>

    <div class="mt-4" x-data="{ open: false }">
        <x-form.button @click="open = ! open">
            <span x-show="! open">Open</span>
            <span x-show="open">Close</span>
        </x-form.button>

        <div class="mt-4" x-show="open" @click.away="open = false">
            AlpineJS is working!
        </div>
    </div>

    <div class="mt-4 space-y-2" x-data="{ input: '' }">
        <x-form.input placeholder="Enter to copy" x-model="input" />
        <x-form.button @click="$clipboard(input)">Copy to Clipboard</x-form.button>
    </div>
</div>
