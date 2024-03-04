@php
	$categoriesOptions ??= [];
	$catDisplayType = data_get($categoriesOptions, 'cat_display_type');
	$maxSubCats = (int)data_get($categoriesOptions, 'max_sub_cats');
	
	$apiResult ??= [];
	$totalCategories = (int)data_get($apiResult, 'meta.total', 0);
	$areCategoriesPagingable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	
	$categories ??= [];
	$subCategories ??= [];
	$category ??= null;
	$hasChildren ??= false;
	$catId ??= 0; /* The selected category ID */
@endphp
@if (!$hasChildren)
	
	{{-- To append in the form (will replace the category field) --}}
	
	@if (!empty($category))
		@if (!empty(data_get($category, 'children')))
			<a href="#browseCategories" data-bs-toggle="modal" class="cat-link" data-id="{{ data_get($category, 'id') }}">
				{{ data_get($category, 'name') }}
			</a>
		@else
			{{ data_get($category, 'name') }}&nbsp;
			[ <a href="#browseCategories"
				 data-bs-toggle="modal"
				 class="cat-link"
				 data-id="{{ data_get($category, 'parent.id', 0) }}"
			><i class="far fa-edit"></i> {{ t('Edit') }}</a> ]
		@endif
	@else
		<a href="#browseCategories" data-bs-toggle="modal" class="cat-link" data-id="0">
			{{ t('select_a_category') }}
		</a>
	@endif
	
@else
	
	{{-- To append in the modal (will replace the modal content) --}}

	@if (!empty($category))
		<p>
			<a href="#" class="btn btn-sm btn-success cat-link" data-id="{{ data_get($category, 'parent_id') }}">
				<i class="fas fa-reply"></i> {{ t('go_to_parent_categories') }}
			</a>&nbsp;
			<strong>{{ data_get($category, 'name') }}</strong>
		</p>
		<div style="clear:both"></div>
	@endif
	
	@if (!empty($categories))
		<div class="col-xl-12 content-box layout-section">
			<div class="row row-featured row-featured-category">
				@if ($catDisplayType == 'c_picture_list')
					
					@foreach($categories as $key => $cat)
						@php
							$_hasChildren = (!empty(data_get($cat, 'children'))) ? 1 : 0;
							$_parentId = data_get($cat, 'parent.id', 0);
							$_hasLink = (data_get($cat, 'id') != $catId || $_hasChildren == 1);
						@endphp
						<div class="col-lg-2 col-md-3 col-sm-4 col-6 f-category">
							@if ($_hasLink)
								<a href="#" class="cat-link"
								   data-id="{{ data_get($cat, 'id') }}"
								   data-parent-id="{{ $_parentId }}"
								   data-has-children="{{ $_hasChildren }}"
								   data-type="{{ data_get($cat, 'type') }}"
								>
							@endif
								<img src="{{ data_get($cat, 'picture_url') }}" class="lazyload img-fluid" alt="{{ data_get($cat, 'name') }}">
								<h6 class="{{ !$_hasLink ? 'text-secondary' : '' }}">
									{{ data_get($cat, 'name') }}
								</h6>
							@if ($_hasLink)
								</a>
							@endif
						</div>
					@endforeach
				
				@elseif ($catDisplayType == 'c_bigIcon_list')
					
					@foreach($categories as $key => $cat)
						@php
							$_hasChildren = (!empty(data_get($cat, 'children'))) ? 1 : 0;
							$_parentId = data_get($cat, 'parent.id', 0);
							$_hasLink = (data_get($cat, 'id') != $catId || $_hasChildren == 1);
						@endphp
						<div class="col-lg-2 col-md-3 col-sm-4 col-6 f-category">
							@if ($_hasLink)
								<a href="#" class="cat-link"
								   data-id="{{ data_get($cat, 'id') }}"
								   data-parent-id="{{ $_parentId }}"
								   data-has-children="{{ $_hasChildren }}"
								   data-type="{{ data_get($cat, 'type') }}"
								>
							@endif
								@if (in_array(config('settings.list.show_category_icon'), [2, 6, 7, 8]))
									<i class="{{ data_get($cat, 'icon_class') ?? 'fas fa-folder' }}"></i>
								@endif
								<h6 class="{{ !$_hasLink ? 'text-secondary' : '' }}">
									{{ data_get($cat, 'name') }}
								</h6>
							@if ($_hasLink)
								</a>
							@endif
						</div>
					@endforeach
				
				@elseif (in_array($catDisplayType, ['cc_normal_list', 'cc_normal_list_s']))
					
					<div style="clear: both;"></div>
					@php
						$styled = ($catDisplayType == 'cc_normal_list_s') ? ' styled' : '';
					@endphp
					<div class="col-xl-12">
						<div class="list-categories-children{{ $styled }}">
							<div class="row">
								@foreach ($categories as $key => $cols)
									<div class="col-md-4 col-sm-4 {{ (count($categories) == $key+1) ? 'last-column' : '' }}">
										@foreach ($cols as $iCat)
											
											@php
												$randomId = '-' . substr(uniqid(rand(), true), 5, 5);
												$_hasChildren = (!empty(data_get($iCat, 'children'))) ? 1 : 0;
												$_parentId = data_get($iCat, 'parent.id', 0);
												$_hasLink = (data_get($iCat, 'id') != $catId || $_hasChildren == 1);
											@endphp
											
											<div class="cat-list">
												<h3 class="cat-title rounded{{ !$_hasLink ? ' text-secondary' : '' }}">
													@if (in_array(config('settings.list.show_category_icon'), [2, 6, 7, 8]))
														<i class="{{ data_get($iCat, 'icon_class') ?? 'fas fa-check' }}"></i>&nbsp;
													@endif
													@if ($_hasLink)
														<a href="#" class="cat-link"
														   data-id="{{ data_get($iCat, 'id') }}"
														   data-parent-id="{{ $_parentId }}"
														   data-has-children="{{ $_hasChildren }}"
														   data-type="{{ data_get($iCat, 'type') }}"
														>
													@endif
														{{ data_get($iCat, 'name') }}
													@if ($_hasLink)
														</a>
													@endif
													<span class="btn-cat-collapsed collapsed"
														  data-bs-toggle="collapse"
														  data-bs-target=".cat-id-{{ data_get($iCat, 'id') . $randomId }}"
														  aria-expanded="false"
													>
															<span class="icon-down-open-big"></span>
														</span>
												</h3>
												<ul class="cat-collapse collapse show cat-id-{{ data_get($iCat, 'id') . $randomId }} long-list-home">
													@php
														$tmpSubCats = data_get($subCategories, data_get($iCat, 'id')) ?? [];
													@endphp
													@if (!empty($tmpSubCats))
														@foreach ($tmpSubCats as $iSubCat)
															@php
																$_hasChildren2 = (!empty(data_get($iSubCat, 'children'))) ? 1 : 0;
																$_parentId2 = data_get($iSubCat, 'parent.id', 0);
																$_hasLink2 = (data_get($iSubCat, 'id') != $catId || $_hasChildren2 == 1);
															@endphp
															<li class="{{ !$_hasLink2 ? 'text-secondary fw-bold' : '' }}">
																@if ($_hasLink2)
																	<a href="#" class="cat-link"
																	   data-id="{{ data_get($iSubCat, 'id') }}"
																	   data-parent-id="{{ $_parentId2 }}"
																	   data-has-children="{{ $_hasChildren2 }}"
																	   data-type="{{ data_get($iSubCat, 'type') }}"
																	>
																@endif
																	{{ data_get($iSubCat, 'name') }}
																@if ($_hasLink2)
																	</a>
																@endif
															</li>
														@endforeach
													@endif
												</ul>
											</div>
										@endforeach
									</div>
								@endforeach
							</div>
						</div>
						<div style="clear: both;"></div>
					</div>
				
				@else
					
					@php
						$listTab = [
							'c_border_list' => 'list-border',
						];
						$catListClass = (isset($listTab[$catDisplayType])) ? 'list ' . $listTab[$catDisplayType] : 'list';
					@endphp
					<div class="col-xl-12">
						<div class="list-categories">
							<div class="row">
								@foreach ($categories as $key => $items)
									<ul class="cat-list {{ $catListClass }} col-md-4 {{ (count($categories) == $key+1) ? 'cat-list-border' : '' }}">
										@foreach ($items as $k => $cat)
											@php
												$_hasChildren = (!empty(data_get($cat, 'children'))) ? 1 : 0;
												$_parentId = data_get($cat, 'parent.id', 0);
												$_hasLink = (data_get($cat, 'id') != $catId || $_hasChildren == 1);
											@endphp
											<li class="{{ !$_hasLink ? 'text-secondary fw-bold' : '' }}">
												@if (in_array(config('settings.list.show_category_icon'), [2, 6, 7, 8]))
													<i class="{{ data_get($cat, 'icon_class') ?? 'fas fa-check' }}"></i>&nbsp;
												@endif
												@if ($_hasLink)
													<a href="#" class="cat-link"
													   data-id="{{ data_get($cat, 'id') }}"
													   data-parent-id="{{ $_parentId }}"
													   data-has-children="{{ $_hasChildren }}"
													   data-type="{{ data_get($cat, 'type') }}"
													>
												@endif
													{{ data_get($cat, 'name') }}
												@if ($_hasLink)
													</a>
												@endif
											</li>
										@endforeach
									</ul>
								@endforeach
							</div>
						</div>
					</div>
				
				@endif
			
			</div>
		</div>
		@if ($totalCategories > 0 && $areCategoriesPagingable)
			<br>
			@include('vendor.pagination.api.bootstrap-4')
		@endif
	@else
		{{ $apiMessage ?? t('no_categories_found') }}
	@endif
@endif

@section('before_scripts')
	@parent
	@if ($maxSubCats >= 0)
		<script>
			var maxSubCats = {{ $maxSubCats }};
		</script>
	@endif
@endsection
