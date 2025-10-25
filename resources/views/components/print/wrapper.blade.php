<div class="sidebar no-print" :class="{ open: isSidebarOpen }">
    <div style="padding:20px 10px; color:#d1d5db;">
        <div style="text-align: right; cursor: pointer; font-size: 120%;" @click="toggleSidebar">&#9932;</div>
        <div class="mt-4">
            <div>{{ trans('print.title') }}</div>
            <div class="mt-1">
                <input type="text" v-model="title" />
            </div>
        </div>
        <div class="mt-4">
            <div>{{ trans('print.sub_title') }}</div>
            <div class="mt-1">
                <input type="text" v-model="subTitle" />
            </div>
        </div>
        <div class="mt-4">
            <div>{{ trans('print.footer_note') }}</div>
            <div class="mt-1">
                <textarea rows="5" v-model="footerNote"></textarea>
            </div>
        </div>
        <div class="mt-4">
            <div>{{ trans('print.margin_top') }}</div>
            <div class="mt-1">
                <input type="text" v-model="marginTop" />
            </div>
        </div>
        <div class="mt-4">
            <div style="display: flex; justify-content: space-between;">
                {{ trans('print.show_header') }}
                <input type="checkbox" v-model="showHeader" />
            </div>
        </div>
        <div class="mt-4">
            <div style="display: flex; justify-content: space-between;">
                {{ trans('print.show_print_time') }}
                <input type="checkbox" v-model="showPrintTime" />
            </div>
        </div>
    </div>
</div>
<span class="menu-toggle no-print" style="font-size: 140%;" v-if="!isSidebarOpen" @click="toggleSidebar">&#9776;</span>


<div :style="{ marginTop: `${marginTop}px` }">

    <div v-if="showHeader">
        @includeFirst([config('config.print.custom_path') . 'header', 'print.header'])
    </div>

    <h1 v-if="title" class="heading" v-text="title"></h1>
    <h1 v-if="subTitle" class="sub-heading" v-text="subTitle"></h1>

    {{ $slot }}

    <div class="footer">
        <div v-text="footerNote"></div>
        <p class="timestamp" v-if="showPrintTime">
            {{ trans('print.printed_at', ['attribute' => Cal::dateTime(now())->formatted]) }}</p>
    </div>
</div>

<script>
    const {
        createApp
    } = Vue

    createApp({
        data() {
            return {
                isSidebarOpen: false,
                title: "{{ isset($meta) ? Arr::get($meta, 'title') : '' }}",
                subTitle: "{{ isset($meta) ? Arr::get($meta, 'sub_title') : '' }}",
                footerNote: "",
                showHeader: true,
                marginTop: 0,
                showPrintTime: true,
            }
        },
        methods: {
            toggleSidebar() {
                this.isSidebarOpen = !this.isSidebarOpen;
            }
        }
    }).mount('#app')
</script>
