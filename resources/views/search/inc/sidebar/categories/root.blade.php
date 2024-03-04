{{-- Category --}}
@if (!empty($cats))
	@php
		$countPostsPerCat ??= [];
	@endphp
	<div id="catsList">
		<div class="block-title has-arrow sidebar-header">
			<h5>
				<span class="fw-bold">
					{{ t('all_categories') }}
				</span> {!! $clearFilterBtn ?? '' !!}
			</h5>
		</div>
		<div class="block-content list-filter categories-list">
			<ul class="list-unstyled">
				@foreach ($cats as $iCat)
					<li>
						@if (isset($cat) && data_get($iCat, 'id') == data_get($cat, 'id'))
							<strong>
								<a href="{{ \App\Helpers\UrlGen::category($iCat, null, $city ?? null) }}" title="{{ data_get($iCat, 'name') }}">
									<span class="title">
										@if (in_array(config('settings.list.show_category_icon'), [4, 5, 6, 8]))
											<i class="{{ data_get($iCat, 'icon_class') ?? 'fas fa-folder' }}"></i>
										@endif
										{{ data_get($iCat, 'name') }}
									</span>
									@if (config('settings.list.count_categories_listings'))
										<span class="count">&nbsp;{{ $countPostsPerCat[data_get($iCat, 'id')]['total'] ?? 0 }}</span>
									@endif
								</a>
							</strong>
						@else
							<a href="{{ \App\Helpers\UrlGen::category($iCat, null, $city ?? null) }}" title="{{ data_get($iCat, 'name') }}">
								<span class="title">
									@if (in_array(config('settings.list.show_category_icon'), [4, 5, 6, 8]))
										<i class="{{ data_get($iCat, 'icon_class') ?? 'fas fa-folder' }}"></i>
									@endif
									{{ data_get($iCat, 'name') }}
								</span>
								@if (config('settings.list.count_categories_listings'))
									<span class="count">&nbsp;{{ $countPostsPerCat[data_get($iCat, 'id')]['total'] ?? 0 }}</span>
								@endif
							</a>
						@endif
					</li>
				@endforeach
			</ul>
		</div>
	</div>
@endif
