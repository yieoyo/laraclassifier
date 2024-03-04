@if (!empty($cat) || !empty($cats))
<div class="container mb-3 hide-xs">
	@if (!empty($cat))
		@if (!empty(data_get($cat, 'children')))
			<div class="row row-cols-lg-4 row-cols-md-3 p-2 g-2" id="categoryBadge">
				@foreach (data_get($cat, 'children') as $iSubCat)
					<div class="col">
						<a href="{{ \App\Helpers\UrlGen::category($iSubCat, null, $city ?? null) }}">
							@if (in_array(config('settings.list.show_category_icon'), [3, 5, 7, 8]))
								<i class="{{ data_get($iSubCat, 'icon_class') ?? 'fas fa-folder' }}"></i>
							@endif
							{{ data_get($iSubCat, 'name') }}
						</a>
					</div>
				@endforeach
			</div>
		@else
			@if (!empty(data_get($cat, 'parent.children')))
				<div class="row row-cols-lg-4 row-cols-md-3 p-2 g-2" id="categoryBadge">
					@foreach (data_get($cat, 'parent.children') as $iSubCat)
						<div class="col">
							@if (data_get($iSubCat, 'id') == data_get($cat, 'id'))
								<span class="fw-bold">
									@if (in_array(config('settings.list.show_category_icon'), [3, 5, 7, 8]))
										<i class="{{ data_get($iSubCat, 'icon_class') ?? 'fas fa-folder' }}"></i>
									@endif
									{{ data_get($iSubCat, 'name') }}
								</span>
							@else
								<a href="{{ \App\Helpers\UrlGen::category($iSubCat, null, $city ?? null) }}">
									@if (in_array(config('settings.list.show_category_icon'), [3, 5, 7, 8]))
										<i class="{{ data_get($iSubCat, 'icon_class') ?? 'fas fa-folder' }}"></i>
									@endif
									{{ data_get($iSubCat, 'name') }}
								</a>
							@endif
						</div>
					@endforeach
				</div>
			@else
				
				@includeFirst([config('larapen.core.customizedViewPath') . 'search.inc.categories-root', 'search.inc.categories-root'])
				
			@endif
		@endif
	@else
		
		@includeFirst([config('larapen.core.customizedViewPath') . 'search.inc.categories-root', 'search.inc.categories-root'])
		
	@endif
</div>
@endif
