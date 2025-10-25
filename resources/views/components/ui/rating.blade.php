@props(['rating' => []])

<div class="flex items-center space-x-1">
    @foreach ($rating as $star)
        <span class="fa-stack" style="width:1em">
            <i class="far fa-star fa-lg fa-stack-1x text-yellow-500"></i>
            @if ($star == 'full')
                <i class="fas fa-star fa-lg fa-stack-1x text-yellow-500"></i>
            @elseif($star == 'half')
                <i class="fas fa-star-half fa-lg fa-stack-1x text-yellow-500"></i>
            @endif
        </span>
    @endforeach
</div>
