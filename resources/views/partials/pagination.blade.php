@if($paginator->hasPages())
<nav class="pagination-nav" aria-label="Pagination">
    @if($paginator->onFirstPage())
        <span class="btn btn-secondary btn-sm pagination-disabled">&laquo; Previous</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-secondary btn-sm">&laquo; Previous</a>
    @endif

    @foreach($elements as $element)
        @if(is_string($element))
            <span class="btn btn-secondary btn-sm pagination-disabled">{{ $element }}</span>
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
        <span class="btn btn-secondary btn-sm pagination-disabled">Next &raquo;</span>
    @endif
</nav>
@endif
