@props(['menu'])

<div class="grid max-w-none grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
    @foreach ($blogs as $blog)
        <x-site.default.blog-card :blog="$blog" :menu="$menu" />
    @endforeach
</div>

{{ $blogs->links() }}
