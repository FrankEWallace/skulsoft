@props(['sliderImages' => []])

<div class="relative z-30">
    <div x-data="{
        currentSlide: 0,
        skip: 1,
        atBeginning: false,
        atEnd: false,
        autoSlideInterval: null,
        startAutoSlide() {
            this.autoSlideInterval = setInterval(() => {
                this.next();
            }, 2500);
        },
        stopAutoSlide() {
            clearInterval(this.autoSlideInterval);
        },
        goToSlide(index) {
            let slider = this.$refs.slider;
            let offset = slider.firstElementChild.getBoundingClientRect().width;
            slider.scrollTo({ left: offset * index, behavior: 'smooth' });
        },
        next() {
            let slider = this.$refs.slider;
            let current = slider.scrollLeft;
            let offset = slider.firstElementChild.getBoundingClientRect().width;
            let maxScroll = offset * (slider.children.length - 1);

            current + offset >= maxScroll ? slider.scrollTo({ left: 0, behavior: 'smooth' }) : slider.scrollBy({ left: offset * this.skip, behavior: 'smooth' });
        },
        prev() {
            let slider = this.$refs.slider;
            let current = slider.scrollLeft;
            let offset = slider.firstElementChild.getBoundingClientRect().width;
            let maxScroll = offset * (slider.children.length - 1);

            current <= 0 ? slider.scrollTo({ left: maxScroll, behavior: 'smooth' }) : slider.scrollBy({ left: -offset * this.skip, behavior: 'smooth' });
        },
        updateButtonStates() {
            let slideEls = this.$el.parentElement.children;
            this.atBeginning = slideEls[0] === this.$el;
            this.atEnd = slideEls[slideEls.length - 1] === this.$el;
        },
        updateCurrentSlide() {
            let slider = this.$refs.slider;
            let offset = slider.firstElementChild.getBoundingClientRect().width;
            this.currentSlide = Math.round(slider.scrollLeft / offset);
        }
    }" x-init="startAutoSlide()" @mouseover="stopAutoSlide()" @mouseout="startAutoSlide()"
        class="flex w-full flex-col">

        <div x-on:keydown.right="next" x-on:keydown.left="prev" tabindex="0" role="region"
            aria-labelledby="carousel-label" class="flex space-x-6">
            <h2 id="carousel-label" class="sr-only" hidden>Carousel</h2>

            <span id="carousel-content-label" class="sr-only" hidden>Carousel</span>

            <ul x-ref="slider" @scroll="updateCurrentSlide" tabindex="0" role="listbox"
                aria-labelledby="carousel-content-label" class="flex w-full snap-x snap-mandatory overflow-x-hidden">
                @foreach ($sliderImages as $sliderImage)
                    <li class="flex w-full shrink-0 snap-start flex-col items-center justify-center p-0" role="option">
                        <img class="w-full" src="{{ Arr::get($sliderImage, 'url') }}" alt="">
                    </li>
                @endforeach
            </ul>
        </div>
        <!-- Prev / Next Buttons -->
        <div class="absolute flex h-full w-full justify-between px-4">
            <!-- Prev Button -->
            <button x-on:click="prev" class="text-6xl" :aria-disabled="atBeginning" :tabindex="atEnd ? -1 : 0">
                <span aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-auto text-gray-300 hover:text-gray-400 lg:h-8"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
                <span class="sr-only">Skip to previous slide page</span>
            </button>

            <!-- Next Button -->
            <button x-on:click="next" class="text-6xl" :aria-disabled="atEnd" :tabindex="atEnd ? -1 : 0">
                <span aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-auto text-gray-300 hover:text-gray-400 lg:h-8"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
                <span class="sr-only">Skip to next slide page</span>
            </button>
        </div>
        <!-- Indicators -->

        <div class="absolute bottom-12 z-10 w-full lg:bottom-24">
            <div class="flex justify-center space-x-2">
                <template x-for="(slide, index) in Array.from($refs.slider.children)" :key="index">
                    <button @click="goToSlide(index)"
                        :class="{ 'bg-gray-500': currentSlide === index, 'bg-gray-300': currentSlide !== index }"
                        class="h-1 w-3 rounded-full hover:bg-gray-400 focus:bg-gray-400 focus:outline-none lg:w-5"></button>
                </template>
            </div>
        </div>

    </div>
</div>

<script src="https://unpkg.com/smoothscroll-polyfill@0.4.4/dist/smoothscroll.js"></script>
