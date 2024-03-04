@php
	$countryCode ??= config('country.code');
	$countryCode = strtolower($countryCode);
	$adminType ??= 0;
	$adminType = !empty($adminType) ? $adminType : config('country.admin_type', 0);
	$relAdminType = (in_array($adminType, ['1', '2'])) ? $adminType : 1;
	$selectedAdminCode = $adminCode ?? 0;
	
	$apiResult ??= [];
	$cities = data_get($apiResult, 'data');
	$totalCities = (int)data_get($apiResult, 'meta.total', 0);
	$areCitiesPagingable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	$admin ??= null;
	
	$languageCode ??= config('app.locale');
	$currSearch ??= [];
	$unWantedInputs ??= [];
	$cityId ??= 0;
	
	$queryArray = (is_array($currSearch)) ? $currSearch : [];
	$cityQueryArray = $queryArray;
@endphp
@if (!empty($cities) && $totalCities > 0)
	@php
		$rowCols = (empty($admin) && $adminType == 2) ? 'row-cols-lg-2 row-cols-md-2 row-cols-sm-1' : 'row-cols-lg-4 row-cols-md-3 row-cols-sm-2';
	@endphp
	<div class="row {{ $rowCols }} row-cols-1">
		@foreach($cities as $city)
			@php
				$relAdmin = data_get($city, 'subAdmin' . $relAdminType);
				$adminCode = data_get($relAdmin, 'code');
				$adminCode = (!empty($adminCode)) ? $adminCode : $selectedAdminCode;
				$adminName = data_get($relAdmin, 'name');
				if ($adminType == 2) {
					$relAdmin1 = data_get($city, 'subAdmin1');
					$admin1Name = data_get($relAdmin1, 'name');
					$adminName = !empty($adminName)
						? (!empty($admin1Name) ? $adminName . ', ' . $admin1Name : $adminName)
						: (!empty($admin1Name) ? $admin1Name : null);
				}
				
				$cityName = data_get($city, 'name');
				$fullCityName = !empty($adminName) ? $cityName . ', ' . $adminName : $cityName;
				$displayedCityName = str($cityName)->limit(25);
			@endphp
			<div class="col mb-1 list-link list-unstyled">
				@if (data_get($city, 'id') == $cityId)
					<strong data-bs-toggle="tooltip" data-bs-custom-class="modal-tooltip" title="{{ $fullCityName }}">
						{{ !empty($admin) ? $displayedCityName : $fullCityName }}
					</strong>
				@else
					@php
						$cityQueryArray = array_merge($cityQueryArray, ['l' => data_get($city, 'id')]);
					@endphp
					<a href="{{ \App\Helpers\UrlGen::search($cityQueryArray, $unWantedInputs) }}"
					   data-bs-toggle="tooltip"
					   data-bs-custom-class="modal-tooltip"
					   title="{{ $fullCityName }}"
					   data-admin-type="{{ $adminType }}"
					   data-admin-code="{{ $adminCode }}"
					   class="is-city"
					   data-id="{{ data_get($city, 'id') }}"
					   data-name="{{ $fullCityName }}"
					>
						{{ !empty($admin) ? $displayedCityName : $fullCityName }}
					</a>
				@endif
			</div>
		@endforeach
	</div>
	@if ($areCitiesPagingable)
		<br>
		@include('vendor.pagination.api.ajax.bootstrap-4')
	@endif
@else
	<div class="row">
		<div class="col-12">
			@if (!empty(data_get($admin, 'code')))
				{{ t('no_cities_found', [], 'global', $languageCode) }}
			@else
				{{ t('admin_division_not_found', [], 'global', $languageCode) }}
			@endif
		</div>
	</div>
@endif