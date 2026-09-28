@if($paginator->hasPages())
<nav style="margin-top: 1.5rem; display: flex; justify-content: center; gap: .25rem; flex-wrap: wrap;">
    @if($paginator->onFirstPage())
        <span class="btn btn-secondary btn-sm" style="opacity: 0.5;">&laquo; Previous</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-secondary btn-sm">&laquo; Previous</a>
    @endif

    @foreach($elements as $element)
        @if(is_string($element))
            <span class="btn btn-secondary btn-sm" style="opacity: 0.5;">{{ $element }}</span>
        @endif
        @if(is_array($element))
            @foreach($element as $page => $url)
                @if($page == $paginator->currentPage())
                    <span class="btn btn-primary btn-sm">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="btn btn-secondary btn-sm">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next &raquo;</a>
    @else
        <span class="btn btn-secondary btn-sm" style="opacity: 0.5;">Next &raquo;</span>
    @endif
</nav>
@endif
