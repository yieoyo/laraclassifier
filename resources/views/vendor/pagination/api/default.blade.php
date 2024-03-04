@php
    $apiResult = $apiResult ?? [];
	$isPagingable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	$paginator = (array)data_get($apiResult, 'links');
	$totalEntries = (int)data_get($apiResult, 'meta.total');
	$currentPage = (int)data_get($apiResult, 'meta.current_page');
	$elements = data_get($apiResult, 'meta.links');
@endphp
@if ($totalEntries > 0 && $isPagingable)
    <style>
        .pagination {
            display: -ms-flexbox;
            flex-wrap: wrap;
            display: flex;
            padding-left: 0;
            list-style: none;
            border-radius: 0.25rem;
        }
    </style>
    <ul class="pagination" role="navigation">
        
        {{-- Previous Page Link --}}
        @if (!data_get($paginator, 'prev'))
            <li class="disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                <span aria-hidden="true">&lsaquo;</span>
            </li>
        @else
            <li>
                <a href="{{ data_get($paginator, 'prev') }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
            </li>
        @endif

        {{-- Pagination Elements --}}
        @if (is_array($elements) && count($elements) > 0)
            @foreach ($elements as $element)
                @continue($loop->first || $loop->last)
                
                {{-- "Three Dots" Separator --}}
                @if (!data_get($element, 'url'))
                    <li class="disabled" aria-disabled="true"><span>{{ data_get($element, 'label') }}</span></li>
                @else
                    {{-- Array Of Links --}}
                    @if ((int)data_get($element, 'label') == $currentPage)
                        <li class="active" aria-current="page"><span>{{ data_get($element, 'label') }}</span></li>
                    @else
                        <li><a href="{{ data_get($element, 'url') }}">{{ data_get($element, 'label') }}</a></li>
                    @endif
                @endif
            @endforeach
        @endif

        {{-- Next Page Link --}}
        @if (data_get($paginator, 'next'))
            <li>
                <a href="{{ data_get($paginator, 'next') }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
            </li>
        @else
            <li class="disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                <span aria-hidden="true">&rsaquo;</span>
            </li>
        @endif
    </ul>
@endif
