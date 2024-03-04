@php
	$bcTab ??= [];
	$admin ??= null;
	$city ??= null;
	
	$adminType = config('country.admin_type', 0);
	$relAdminType = (in_array($adminType, ['1', '2'])) ? $adminType : 1;
	$adminCode = data_get($city, 'subadmin' . $relAdminType . '_code') ?? data_get($admin, 'code') ?? 0;
	
	// Search base URL
	$searchWithoutQuery = \App\Helpers\UrlGen::searchWithoutQuery();
	$filterBy = request()->query('filterBy');
	if (!empty($filterBy)) {
		$searchWithoutQuery .=  (str_contains($searchWithoutQuery, '?')) ? '&' : '?';
		$searchWithoutQuery .= 'filterBy=' . $filterBy;
	}
@endphp
<div class="container">
	<nav aria-label="breadcrumb" role="navigation" class="search-breadcrumb">
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="fas fa-home"></i></a></li>
			<li class="breadcrumb-item">
				<a href="{{ $searchWithoutQuery }}">
					{{ config('country.name') }}
				</a>
			</li>
			@if (is_array($bcTab) && count($bcTab) > 0)
				@foreach($bcTab as $key => $value)
					@if ($value->has('position') && $value->get('position') > count($bcTab)+1)
						<li class="breadcrumb-item active">
							{!! $value->get('name') !!}
							&nbsp;
							@if (!empty($adminCode))
								<a href="#browseLocations" data-bs-toggle="modal" data-admin-code="{{ $adminCode }}" data-city-id="{{ data_get($city, 'id', 0) }}">
									<span class="caret"></span>
								</a>
							@endif
						</li>
					@else
						<li class="breadcrumb-item"><a href="{{ $value->get('url') }}">{!! $value->get('name') !!}</a></li>
					@endif
				@endforeach
			@endif
		</ol>
	</nav>
</div>
