@php
	$sectionOptions = $getStatsOp ?? [];
	$sectionData ??= [];
	$stats = (array)data_get($sectionData, 'count');
	
	$iconPosts = $sectionOptions['icon_count_listings'] ?? 'fas fa-bullhorn';
	$iconUsers = $sectionOptions['icon_count_users'] ?? 'fas fa-users';
	$iconLocations = $sectionOptions['icon_count_locations'] ?? 'far fa-map';
	$prefixPosts = $sectionOptions['prefix_count_listings'] ?? '';
	$suffixPosts = $sectionOptions['suffix_count_listings'] ?? '';
	$prefixUsers = $sectionOptions['prefix_count_users'] ?? '';
	$suffixUsers = $sectionOptions['suffix_count_users'] ?? '';
	$prefixLocations = $sectionOptions['prefix_count_locations'] ?? '';
	$suffixLocations = $sectionOptions['suffix_count_locations'] ?? '';
	$disableCounterUp = $sectionOptions['disable_counter_up'] ?? false;
	$counterUpDelay = $sectionOptions['counter_up_delay'] ?? 10;
	$counterUpTime = $sectionOptions['counter_up_time'] ?? 2000;
	$hideOnMobile = (data_get($sectionOptions, 'hide_on_mobile') == '1') ? ' hidden-sm' : '';
@endphp

@includeFirst([config('larapen.core.customizedViewPath') . 'home.inc.spacer', 'home.inc.spacer'], ['hideOnMobile' => $hideOnMobile])

<div class="container{{ $hideOnMobile }}">
	<div class="page-info page-info-lite rounded">
		<div class="text-center section-promo">
			<div class="row">
				
				<div class="col-sm-4 col-12">
					<div class="iconbox-wrap">
						<div class="iconbox">
							<div class="iconbox-wrap-icon">
								<i class="{{ $iconPosts }}"></i>
							</div>
							<div class="iconbox-wrap-content">
								<h5>
									@if (!empty($prefixPosts))<span>{{ $prefixPosts }}</span>@endif
									<span class="counter">{{ (int)data_get($stats, 'posts') }}</span>
									@if (!empty($suffixPosts))<span>{{ $suffixPosts }}</span>@endif
								</h5>
								<div class="iconbox-wrap-text">{{ t('classified_ads') }}</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="col-sm-4 col-12">
					<div class="iconbox-wrap">
						<div class="iconbox">
							<div class="iconbox-wrap-icon">
								<i class="{{ $iconUsers }}"></i>
							</div>
							<div class="iconbox-wrap-content">
								<h5>
									@if (!empty($prefixUsers))<span>{{ $prefixUsers }}</span>@endif
									<span class="counter">{{ (int)data_get($stats, 'users') }}</span>
									@if (!empty($suffixUsers))<span>{{ $suffixUsers }}</span>@endif
								</h5>
								<div class="iconbox-wrap-text">{{ t('Trusted Sellers') }}</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="col-sm-4 col-12">
					<div class="iconbox-wrap">
						<div class="iconbox">
							<div class="iconbox-wrap-icon">
								<i class="{{ $iconLocations }}"></i>
							</div>
							<div class="iconbox-wrap-content">
								<h5>
									@if (!empty($prefixLocations))<span>{{ $prefixLocations }}</span>@endif
									<span class="counter">{{ (int)data_get($stats, 'locations') }}</span>
									@if (!empty($suffixLocations))<span>{{ $suffixLocations }}</span>@endif
								</h5>
								<div class="iconbox-wrap-text">{{ t('locations') }}</div>
							</div>
						</div>
					</div>
				</div>
	
			</div>
		</div>
	</div>
</div>

@section('after_scripts')
	@parent
	@if (!isset($disableCounterUp) || !$disableCounterUp)
		<script>
			const counterUp = window.counterUp.default;
			const counterEl = document.querySelector('.counter');
			counterUp(counterEl, {
				duration: {{ $counterUpTime }},
				delay: {{ $counterUpDelay }}
			});
		</script>
	@endif
@endsection
