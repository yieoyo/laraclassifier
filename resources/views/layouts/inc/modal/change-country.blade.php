@php
	$countries ??= collect();
	$countryFlagShape = config('settings.localization.country_flag_shape');
@endphp
{{-- Modal Change Country --}}
<div class="modal fade modalHasList" id="selectCountry" tabindex="-1" aria-labelledby="selectCountryLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			
			<div class="modal-header px-3">
				<h4 class="modal-title uppercase fw-bold" id="selectCountryLabel">
					<i class="far fa-map"></i> {{ t('select_country') }}
				</h4>
				
				<button type="button" class="close" data-bs-dismiss="modal">
					<span aria-hidden="true">&times;</span>
					<span class="sr-only">{{ t('Close') }}</span>
				</button>
			</div>
			
			<div class="modal-body">
				<div class="row row-cols-lg-4 row-cols-md-3 row-cols-sm-2 row-cols-2">
					
					@if ($countries->isNotEmpty())
						@foreach ($countries as $code => $country)
							<div class="col mb-1 cat-list">
								@php
									$countryUrl = dmUrl($country, '/', true, !config('plugins.domainmapping.installed'));
									$countryName = $country->get('name');
									$countryNameLimited = str($countryName)->limit(21)->toString();
								@endphp
								@if ($countryFlagShape == 'rectangle')
									<img src="{{ url('images/blank.gif') . getPictureVersion() }}"
										 class="flag flag-{{ $country->get('icode') }}"
										 style="margin-bottom: 4px; margin-right: 5px;"
									     alt="{{ $countryNameLimited }}"
									>
								@else
									<img src="{{ $country->get('flag16_url') }}"
									     class=""
									     style="margin-bottom: 4px; margin-right: 5px;"
									     alt="{{ $countryNameLimited }}"
									>
								@endif
								<a href="{{ $countryUrl }}"
								   data-bs-toggle="tooltip"
								   data-bs-custom-class="modal-tooltip"
								   title="{{ $countryName }}"
								>
									{{ $countryNameLimited }}
								</a>
							</div>
						@endforeach
					@endif
					
				</div>
			</div>
			
		</div>
	</div>
</div>
