@php
    $apiResult ??= [];
	$isPaginable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	$paginator = data_get($apiResult, 'links');
@endphp
@if ($isPaginable)
    <div class="btn-group btn-group-sm">
        {{-- Previous Page Link --}}
        @if (!data_get($apiResult, 'links.prev'))
            <button type="button" class="btn btn-secondary disabled" aria-disabled="true">
                <span class="fas fa-arrow-left"></span>
            </button>
        @else
            <a class="btn btn-secondary" href="{{ data_get($paginator, 'prev') }}" rel="prev">
                <span class="fas fa-arrow-left"></span>
            </a>
        @endif
        
        {{-- Next Page Link --}}
        @if (data_get($paginator, 'next'))
            <a class="btn btn-secondary" href="{{ data_get($paginator, 'next') }}" rel="next">
                <span class="fas fa-arrow-right"></span>
            </a>
        @else
            <button type="button" class="btn btn-secondary disabled" aria-disabled="true">
                <span class="fas fa-arrow-right"></span>
            </button>
        @endif
    </div>
@endif
