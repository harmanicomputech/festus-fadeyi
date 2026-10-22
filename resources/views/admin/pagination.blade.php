@if ($paginator->hasPages())
    <nav aria-label="Pagination">
        @if (! $paginator->onFirstPage())<a class="btn btn--ghost btn--sm" href="{{ $paginator->previousPageUrl() }}">Previous</a>@endif
        <span class="small muted" style="align-self:center">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())<a class="btn btn--ghost btn--sm" href="{{ $paginator->nextPageUrl() }}">Next</a>@endif
    </nav>
@endif
