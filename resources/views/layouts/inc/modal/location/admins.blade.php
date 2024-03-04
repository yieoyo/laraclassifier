@php
	$countryCode ??= config('country.code');
	$countryCode = strtolower($countryCode);
	$adminType ??= 0;
	
	$apiResult ??= [];
	$admins = data_get($apiResult, 'data');
	$totalAdmins = (int)data_get($apiResult, 'meta.total', 0);
	$areAdminsPagingable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	
	$languageCode ??= config('app.locale');
	$currSearch ??= [];
	$unWantedInputs ??= [];
	
	$queryArray = (is_array($currSearch)) ? $currSearch : [];
	$adminQueryArray = $queryArray;
	if (isset($adminQueryArray['distance'])) {
		unset($adminQueryArray['distance']);
	}
	$queryString = !empty($adminQueryArray) ? '?' . http_build_query($adminQueryArray) : '';
@endphp
@if (!empty($admins) && $totalAdmins > 0)
	@php
		$rowCols = ($adminType == 2) ? 'row-cols-lg-3 row-cols-md-2 row-cols-sm-1' : 'row-cols-lg-4 row-cols-md-3 row-cols-sm-2';
	@endphp
	<div class="row {{ $rowCols }} row-cols-1">
		@php
			$url = url('ajax/locations/' . $countryCode . '/cities');
			$url = $url . $queryString;
		@endphp
		<div class="col mb-1 list-link list-unstyled">
			<a href="" data-url="{{ $url }}" class="is-admin">
				{{ t('all_cities', [], 'global', $languageCode) }}
			</a>
		</div>
		@foreach($admins as $admin)
			@php
				$url = url('ajax/locations/' . $countryCode . '/admins/' . $adminType . '/' . data_get($admin, 'code') . '/cities');
				$url = $url . $queryString;
				
				$admin1 = null;
				$adminName = data_get($admin, 'name');
				if ($adminType == 2) {
					$admin1 = data_get($admin, 'subAdmin1');
					$admin1Name = data_get($admin1, 'name');
					$fullAdminName = !empty($admin1Name) ? $adminName . ', ' . $admin1Name : $adminName;
				} else {
					$fullAdminName = $adminName;
				}
			@endphp
			<div class="col mb-1 list-link list-unstyled">
				<a href=""
				   data-url="{{ $url }}"
				   class="is-admin"
				   data-bs-toggle="tooltip"
				   data-bs-custom-class="modal-tooltip"
				   title="{{ $fullAdminName }}"
				>
					{{ $fullAdminName }}
				</a>
			</div>
		@endforeach
	</div>
	@if ($areAdminsPagingable)
		<br>
		@include('vendor.pagination.api.ajax.bootstrap-4')
	@endif
@else
	<div class="row">
		<div class="col-12">
			{{ t('no_admin_divisions_found', [], 'global', $languageCode) }}
		</div>
	</div>
@endif