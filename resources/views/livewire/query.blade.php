<form wire:submit="submit">
    <div class="w-full">
        <div class="mb-5">
            <label for="queryName" class="block text-sm font-medium mb-1 text-default-600">Name </label>
            <input type="text" id="queryName"
                class="py-2 px-4 leading-6 block w-full border-default-300 rounded text-sm focus:border-default-300 focus:ring-0"
                wire:model.live.debounce.500ms="name" placeholder="Your Name">
            @error('name')
                <p class="text-danger helper-message mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="flex gap-6">
        <div class="md:w-1/2">
            <div class="mb-5">
                <label for="queryEmail" class="block text-sm font-medium mb-1 text-default-600">Email </label>
                <input type="text" id="queryEmail"
                    class="py-2 px-4 leading-6 block w-full border-default-300 rounded text-sm focus:border-default-300 focus:ring-0"
                    wire:model.live.debounce.500ms="email" placeholder="Your Email">
                @error('email')
                    <p class="text-danger helper-message mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="md:w-1/2">
            <div class="mb-5">
                <label for="queryPhone" class="block text-sm font-medium mb-1 text-default-600">Phone </label>
                <input type="text" id="queryPhone"
                    class="py-2 px-4 leading-6 block w-full border-default-300 rounded text-sm focus:border-default-300 focus:ring-0"
                    wire:model.live.debounce.500ms="phone" placeholder="Your Phone">
                @error('phone')
                    <p class="text-danger helper-message mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
    <div class="w-full">
        <div class="mb-5">
            <label for="querySubject" class="block text-sm font-medium mb-1 text-default-600">Subject </label>
            <input type="text" id="querySubject"
                class="py-2 px-4 leading-6 block w-full border-default-300 rounded text-sm focus:border-default-300 focus:ring-0"
                wire:model.live.debounce.500ms="subject" placeholder="Your Subject">
            @error('subject')
                <p class="text-danger helper-message mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-5">
            <label for="queryMessage" class="block text-sm font-medium mb-1 text-default-600">Message
            </label>
            <textarea
                class="py-2 px-4 leading-6 block w-full border-default-300 rounded text-sm focus:border-default-300 focus:ring-0"
                id="queryMessage" rows="4" wire:model.live.debounce.500ms="message" placeholder="Type Your Message..."></textarea>
            @error('message')
                <p class="text-danger helper-message mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <button type="submit"
        class="inline-flex items-center text-sm bg-site-primary text-white font-medium leading-6 text-center align-middle select-none py-2 px-4 rounded-md transition-all hover:shadow-lg hover:bg-site-lite-primary">
        Send
        <i class="fas fa-paper-plane size-4 ms-1"></i>
    </button>

    @if ($response)
        <div class="{{ $error ? 'bg-danger' : 'bg-success' }} my-2 p-2 rounded-md text-white">
            {{ $response }}
        </div>
    @endif
</form>
