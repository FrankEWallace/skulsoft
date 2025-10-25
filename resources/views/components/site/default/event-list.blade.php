@foreach ($events as $event)
    {{ $event->title }}
@endforeach

{{ $events->links() }}
