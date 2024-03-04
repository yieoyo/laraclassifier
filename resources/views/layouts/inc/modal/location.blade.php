@php
	$admin ??= null;
	$city ??= null;
	
	$adminType = config('country.admin_type', 0);
	$relAdminType = (in_array($adminType, ['1', '2'])) ? $adminType : 1;
	$adminCode = data_get($city, 'subadmin' . $relAdminType . '_code') ?? data_get($admin, 'code') ?? 0;
	
	$inputs = request()->all();
	$currSearch = base64_encode(serialize($inputs));
@endphp
{{-- Modal Select City --}}
<div class="modal fade" id="browseLocations" tabindex="-1" aria-labelledby="browseLocationsLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			
			<div class="modal-header px-3">
				<h4 class="modal-title" id="browseLocationsLabel">
					<i class="far fa-map"></i> {{ t('select_a_location') }}
				</h4>
				
				<button type="button" class="close" data-bs-dismiss="modal">
					<span aria-hidden="true">&times;</span>
					<span class="sr-only">{{ t('Close') }}</span>
				</button>
			</div>
			
			<div class="modal-body">
				<div class="row">
					<div class="col-12">
						<div id="locationsTitle" style="height: 40px;" class="align-middle">
							{!! t('locations_in_country', ['country' => config('country.name')]) !!}
						</div>
						<div style="clear:both"></div>
						
						<div class="col-12 no-padding">
							<form id="locationsModalForm" method="POST">
								<input type="hidden" id="modalCountryChanged" name="country_changed" value="0">
								<input type="hidden" id="modalTriggerName" name="trigger_name" value="">
								<input type="hidden" id="modalUrl" name="url" value="">
								<input type="hidden" id="modalAdminType" name="admin_type" value="{{ $adminType }}">
								<input type="hidden" id="modalAdminCode" name="admin_code" value="">
								<input type="hidden" id="currSearch" name="curr_search" value="{!! $currSearch !!}">
								
								<div class="row g-3">
									<div class="col-sm-12 col-md-11 col-lg-10">
										<div class="input-group position-relative d-inline-flex align-items-center">
											<input type="text"
												   id="modalQuery"
												   name="query"
												   class="form-control input-md"
												   placeholder="{{ t('search_a_location') }}"
												   aria-label="{{ t('search_a_location') }}"
												   value=""
												   autocomplete="off"
											>
											<span class="input-group-text">
												<i id="modalQueryClearBtn" class="bi bi-x-lg" style="cursor: pointer;"></i>
											</span>
										</div>
									</div>
									<div class="col-sm-12 col-md-3 col-lg-2">
										<button id="modalQuerySearchBtn" class="btn btn-primary btn-block"> {{ t('find') }} </button>
									</div>
								</div>
								
								{!! csrf_field() !!}
							</form>
						</div>
						<div style="clear:both"></div>
						
						<hr class="border-0 bg-secondary">
					</div>
					<div class="col-12" id="locationsList"></div>
				</div>
			</div>
			
		</div>
	</div>
</div>

@section('after_scripts')
	@parent
	<script>
		{{-- Modal Default Admin1 Code --}}
		var defaultAdminType = '{{ $adminType }}';
		var defaultAdminCode = '{{ $adminCode }}';
	</script>
	<script src="{{ url('assets/js/app/browse.locations.js') . vTime() }}"></script>
@endsection
