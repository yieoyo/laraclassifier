@php
	$countPostsPerCat ??= [];
	
	// Clear Filter Button
	$clearFilterBtn = \App\Helpers\UrlGen::getCategoryFilterClearLink($cat ?? null, $city ?? null);
@endphp
@if (!empty($cat))
	@php
		$catParentUrl = \App\Helpers\UrlGen::getCatParentUrl(data_get($cat, 'parent') ?? null, $city ?? null);
	@endphp
	
	{{-- SubCategory --}}
	<div id="subCatsList">
		@if (!empty(data_get($cat, 'children')))
			
			<div class="block-title has-arrow sidebar-header">
				<h5>
				<span class="fw-bold">
					@if (!empty(data_get($cat, 'parent')))
						<a href="{{ \App\Helpers\UrlGen::category(data_get($cat, 'parent'), null, $city ?? null) }}">
							<i class="fas fa-reply"></i> {{ data_get($cat, 'parent.name') }}
						</a>
					@else
						<a href="{{ $catParentUrl }}">
							<i class="fas fa-reply"></i> {{ t('all_categories') }}
						</a>
					@endif
				</span> {!! $clearFilterBtn !!}
				</h5>
			</div>
			<div class="block-content list-filter categories-list">
				<ul class="list-unstyled">
					<li>
						<a href="{{ \App\Helpers\UrlGen::category($cat, null, $city ?? null) }}" title="{{ data_get($cat, 'name') }}">
							<span class="title fw-bold">
								@if (in_array(config('settings.list.show_category_icon'), [4, 5, 6, 8]))
									<i class="{{ data_get($cat, 'icon_class') ?? 'fas fa-folder' }}"></i>
								@endif
								{{ data_get($cat, 'name') }}
							</span>
							@if (config('settings.list.count_categories_listings'))
								<span class="count">&nbsp;({{ $countPostsPerCat[data_get($cat, 'id')]['total'] ?? 0 }})</span>
							@endif
						</a>
						<ul class="list-unstyled long-list">
							@foreach (data_get($cat, 'children') as $iSubCat)
								<li>
									<a href="{{ \App\Helpers\UrlGen::category($iSubCat, null, $city ?? null) }}" title="{{ data_get($iSubCat, 'name') }}">
										@if (in_array(config('settings.list.show_category_icon'), [4, 5, 6, 8]))
											<i class="{{ data_get($iSubCat, 'icon_class') ?? 'fas fa-folder' }}"></i>
										@endif
										{{ str(data_get($iSubCat, 'name'))->limit(100) }}
										@if (config('settings.list.count_categories_listings'))
											<span class="count">&nbsp;({{ $countPostsPerCat[data_get($iSubCat, 'id')]['total'] ?? 0 }})</span>
										@endif
									</a>
								</li>
							@endforeach
						</ul>
					</li>
				</ul>
			</div>
			
		@else
			
			@if (!empty(data_get($cat, 'parent.children')))
				<div class="block-title has-arrow sidebar-header">
					<h5>
						<span class="fw-bold">
							@if (!empty(data_get($cat, 'parent.parent')))
								<a href="{{ \App\Helpers\UrlGen::category(data_get($cat, 'parent.parent'), null, $city ?? null) }}">
									<i class="fas fa-reply"></i> {{ data_get($cat, 'parent.parent.name') }}
								</a>
							@elseif (!empty(data_get($cat, 'parent')))
								<a href="{{ \App\Helpers\UrlGen::category(data_get($cat, 'parent'), null, $city ?? null) }}">
									<i class="fas fa-reply"></i> {{ data_get($cat, 'name') }}
								</a>
							@else
								<a href="{{ $catParentUrl }}">
									<i class="fas fa-reply"></i> {{ t('all_categories') }}
								</a>
							@endif
						</span> {!! $clearFilterBtn !!}
					</h5>
				</div>
				<div class="block-content list-filter categories-list">
					<ul class="list-unstyled">
						@foreach (data_get($cat, 'parent.children') as $iSubCat)
							<li>
								@if (data_get($iSubCat, 'id') == data_get($cat, 'id'))
									<strong>
										<a href="{{ \App\Helpers\UrlGen::category($iSubCat, null, $city ?? null) }}" title="{{ data_get($iSubCat, 'name') }}">
											@if (in_array(config('settings.list.show_category_icon'), [4, 5, 6, 8]))
												<i class="{{ data_get($iSubCat, 'icon_class') ?? 'fas fa-folder' }}"></i>
											@endif
											{{ str(data_get($iSubCat, 'name'))->limit(100) }}
											@if (config('settings.list.count_categories_listings'))
												<span class="count">&nbsp;({{ $countPostsPerCat[data_get($iSubCat, 'id')]['total'] ?? 0 }})</span>
											@endif
										</a>
									</strong>
								@else
									<a href="{{ \App\Helpers\UrlGen::category($iSubCat, null, $city ?? null) }}" title="{{ data_get($iSubCat, 'name') }}">
										@if (in_array(config('settings.list.show_category_icon'), [4, 5, 6, 8]))
											<i class="{{ data_get($iSubCat, 'icon_class') ?? 'fas fa-folder' }}"></i>
										@endif
										{{ str(data_get($iSubCat, 'name'))->limit(100) }}
										@if (config('settings.list.count_categories_listings'))
											<span class="count">&nbsp;({{ $countPostsPerCat[data_get($iSubCat, 'id')]['total'] ?? 0 }})</span>
										@endif
									</a>
								@endif
							</li>
						@endforeach
					</ul>
				</div>
			@else
				
				@includeFirst(
					[config('larapen.core.customizedViewPath') . 'search.inc.sidebar.categories.root', 'search.inc.sidebar.categories.root'],
					['countPostsPerCat' => $countPostsPerCat]
				)
			
			@endif
			
		@endif
	</div>
	
@else
	
	@includeFirst(
		[config('larapen.core.customizedViewPath') . 'search.inc.sidebar.categories.root', 'search.inc.sidebar.categories.root'],
		['countPostsPerCat' => $countPostsPerCat]
	)
	
@endif
<div style="clear:both"></div>
