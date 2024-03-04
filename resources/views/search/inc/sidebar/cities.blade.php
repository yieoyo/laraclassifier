<?php
// Clear Filter Button
$clearFilterBtn = \App\Helpers\UrlGen::getCityFilterClearLink($cat ?? null, $city ?? null);
?>
<?php
/*
 * Check if the City Model exists in the Cities eloquent collection
 * If it doesn't exist in the collection,
 * Then, add it into the Cities eloquent collection
 */
if (isset($cities, $city) && !collect($cities)->contains($city)) {
	collect($cities)->push($city)->toArray();
}
?>
{{-- City --}}
<div class="block-title has-arrow sidebar-header">
	<h5>
		<span class="fw-bold">
			{{ t('locations') }}
		</span> {!! $clearFilterBtn !!}
	</h5>
</div>
<div class="block-content list-filter locations-list">
	<ul class="browse-list list-unstyled long-list">
		@if (isset($cities) && !empty($cities))
			@foreach ($cities as $iCity)
				<li>
					@if (
						(
							isset($city)
							&& data_get($city, 'id') == data_get($iCity, 'id')
						)
						|| request()->input('l') == data_get($iCity, 'id')
						)
						<strong>
							<a href="{!! \App\Helpers\UrlGen::city($iCity, null, $cat ?? null) !!}" title="{{ data_get($iCity, 'name') }}">
								{{ data_get($iCity, 'name') }}
								@if (config('settings.list.count_cities_listings'))
									<span class="count">&nbsp;{{ data_get($iCity, 'posts_count') ?? 0 }}</span>
								@endif
							</a>
						</strong>
					@else
						<a href="{!! \App\Helpers\UrlGen::city($iCity, null, $cat ?? null) !!}" title="{{ data_get($iCity, 'name') }}">
							{{ data_get($iCity, 'name') }}
							@if (config('settings.list.count_cities_listings'))
								<span class="count">&nbsp;{{ data_get($iCity, 'posts_count') ?? 0 }}</span>
							@endif
						</a>
					@endif
				</li>
			@endforeach
		@endif
	</ul>
</div>
<div style="clear:both"></div>