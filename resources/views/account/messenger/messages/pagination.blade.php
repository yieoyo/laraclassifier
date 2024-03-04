@php
    $apiResult ??= [];
	$isPaginable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	$paginator = data_get($apiResult, 'links');
@endphp
@if ($isPaginable)
    {{-- Next Page Link --}}
    @if (data_get($paginator, 'next'))
        <span class="text-muted">
            <a class="btn btn-sm btn-secondary rounded mb-3" href="{{ data_get($paginator, 'next') }}" rel="next">
                {{ t('Load old messages') }}
            </a>
        </span>
    @endif
@endif
