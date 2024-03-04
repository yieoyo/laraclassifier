<?php
$sectionOptions = $getLocationsOp ?? [];
$sectionData ??= [];
$cities = (array)data_get($sectionData, 'cities');

// Get Admin Map's values
$locCanBeShown = (data_get($sectionOptions, 'show_cities') == '1');
$locColumns = (int)(data_get($sectionOptions, 'items_cols') ?? 3);
$locCountListingsPerCity = (config('settings.list.count_cities_listings'));
$mapCanBeShown = (
	file_exists(config('larapen.core.maps.path') . config('country.icode') . '.svg')
	&& data_get($sectionOptions, 'show_map') == '1'
);

$showListingBtn = (data_get($sectionOptions, 'show_listing_btn') == '1');

$hideOnMobile = (data_get($sectionOptions, 'hide_on_mobile') == '1') ? ' hidden-sm' : '';
?>
@if ($locCanBeShown || $mapCanBeShown)
@includeFirst([config('larapen.core.customizedViewPath') . 'home.inc.spacer', 'home.inc.spacer'], ['hideOnMobile' => $hideOnMobile])
<div class="container{{ $hideOnMobile }}">
	<div class="col-xl-12 page-content p-0">
		<div class="inner-box">
			
			<div class="row">
				@if (!$mapCanBeShown)
					<div class="row">
						<div class="col-xl-12 col-sm-12">
							<h2 class="title-3 pt-1 pb-3 px-3" style="white-space: nowrap;">
								<i class="fas fa-map-marker-alt"></i>&nbsp;{{ t('Choose a city') }}
							</h2>
						</div>
					</div>
				@endif
				<?php
				$leftClassCol = '';
				$rightClassCol = '';
				$ulCol = 'col-md-3 col-sm-12'; // Cities Columns
				
				if ($locCanBeShown && $mapCanBeShown) {
					// Display the Cities & the Map
					$leftClassCol = 'col-lg-8 col-md-12';
					$rightClassCol = 'col-lg-3 col-md-12 mt-3 mt-xl-0 mt-lg-0';
					$ulCol = 'col-md-4 col-sm-6 col-12';
					
					if ($locColumns == 2) {
						$leftClassCol = 'col-md-6 col-sm-12';
						$rightClassCol = 'col-md-5 col-sm-12';
						$ulCol = 'col-md-6 col-sm-12';
					}
					if ($locColumns == 1) {
						$leftClassCol = 'col-md-3 col-sm-12';
						$rightClassCol = 'col-md-8 col-sm-12';
						$ulCol = 'col-xl-12';
					}
				} else {
					if ($locCanBeShown && !$mapCanBeShown) {
						// Display the Cities & Hide the Map
						$leftClassCol = 'col-xl-12';
					}
					if (!$locCanBeShown && $mapCanBeShown) {
						// Display the Map & Hide the Cities
						$rightClassCol = 'col-xl-12';
					}
				}
				?>
				@if ($locCanBeShown)
					<div class="{{ $leftClassCol }} page-content m-0 p-0">
						@if (!empty($cities))
							<div class="relative location-content">
								
								@if ($mapCanBeShown)
									<h2 class="title-3 pt-1 pb-3 px-3" style="white-space: nowrap;">
										<i class="fas fa-map-marker-alt"></i>&nbsp;{{ t('Choose a city or region') }}
									</h2>
								@endif
								<div class="col-xl-12 tab-inner">
									<div class="row">
										@foreach ($cities as $key => $items)
											<ul class="cat-list {{ $ulCol }} mb-0 mb-xl-2 mb-lg-2 mb-md-2 {{ (count($cities) == $key+1) ? 'cat-list-border' : '' }}">
												@foreach ($items as $k => $city)
													<li>
														@if (data_get($city, 'id') == 0)
															<a href="#browseLocations" data-bs-toggle="modal" data-admin-code="0" data-city-id="0">
																{!! data_get($city, 'name') !!}
															</a>
														@else
															<a href="{{ \App\Helpers\UrlGen::city($city) }}">
																{{ data_get($city, 'name') }}
															</a>
															@if ($locCountListingsPerCity)
																&nbsp;({{ data_get($city, 'posts_count') ?? 0 }})
															@endif
														@endif
													</li>
												@endforeach
											</ul>
										@endforeach
									</div>
								</div>
								
								@if ($showListingBtn)
									@if (!auth()->check() && config('settings.single.guest_can_submit_listings') != '1')
										<a class="btn btn-lg btn-listing" href="#quickLogin" data-bs-toggle="modal">
											<i class="far fa-edit"></i> {{ t('Create Listing') }}
										</a>
									@else
										<a class="btn btn-lg btn-listing ps-4 pe-4" href="{{ \App\Helpers\UrlGen::addPost() }}" style="text-transform: none;">
											<i class="far fa-edit"></i> {{ t('Create Listing') }}
										</a>
									@endif
								@endif
		
							</div>
						@endif
					</div>
				@endif
				
				@includeFirst([config('larapen.core.customizedViewPath') . 'home.inc.locations.svgmap', 'home.inc.locations.svgmap'])
			</div>
			
		</div>
	</div>
</div>
@endif

@section('modal_location')
	@parent
	@if ($locCanBeShown || $mapCanBeShown)
		@includeFirst([config('larapen.core.customizedViewPath') . 'layouts.inc.modal.location', 'layouts.inc.modal.location'])
	@endif
@endsection
