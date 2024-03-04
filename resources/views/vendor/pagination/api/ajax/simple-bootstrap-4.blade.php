@php
    $apiResult = $apiResult ?? [];
	$isPagingable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	$paginator = data_get($apiResult, 'links');
@endphp
@if ($isPagingable)
    <ul class="pagination justify-content-center" role="navigation">
        {{-- Previous Page Link --}}
        @if (!data_get($apiResult, 'links.prev'))
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link">@lang('pagination.previous')</span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link" href="" rel="prev" data-url="{{ data_get($paginator, 'prev') }}">@lang('pagination.previous')</a>
            </li>
        @endif

        {{-- Next Page Link --}}
        @if (data_get($paginator, 'next'))
            <li class="page-item">
                <a class="page-link" href="" rel="next" data-url="{{ data_get($paginator, 'next') }}">@lang('pagination.next')</a>
            </li>
        @else
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link">@lang('pagination.next')</span>
            </li>
        @endif
    </ul>
@endif
