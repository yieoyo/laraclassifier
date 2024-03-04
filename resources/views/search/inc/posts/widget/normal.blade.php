@php
	$widget ??= [];
	$posts = (array)data_get($widget, 'posts');
	$totalPosts = (int)data_get($widget, 'totalPosts', 0);
	
	$sectionOptions ??= [];
	$hideOnMobile = (data_get($sectionOptions, 'hide_on_mobile') == '1') ? ' hidden-sm' : '';
	
	$isFromHome ??= false;
@endphp
@if ($totalPosts > 0)
	@if ($isFromHome)
		@includeFirst([
			config('larapen.core.customizedViewPath') . 'home.inc.spacer',
			'home.inc.spacer'
		], ['hideOnMobile' => $hideOnMobile])
	@endif
	<div class="container{{ $isFromHome ? '' : ' my-3' }}{{ $hideOnMobile }}">
		<div class="col-xl-12 content-box layout-section">
			<div class="row row-featured row-featured-category">
				
				<div class="col-xl-12 box-title no-border">
					<div class="inner">
						<h2>
							<span class="title-3">{!! data_get($widget, 'title') !!}</span>
							<a href="{{ data_get($widget, 'link') }}" class="sell-your-item">
								{{ t('View more') }} <i class="fas fa-bars"></i>
							</a>
						</h2>
					</div>
				</div>
				
				<div class="col-xl-12">
					<div class="category-list {{ config('settings.list.display_mode', 'make-grid') }} noSideBar">
						<div id="postsList" class="category-list-wrapper posts-wrapper row no-margin">
							@if (config('settings.list.display_mode') == 'make-list')
								@includeFirst([
									config('larapen.core.customizedViewPath') . 'search.inc.posts.template.list',
									'search.inc.posts.template.list'
								])
							@elseif (config('settings.list.display_mode') == 'make-compact')
								@includeFirst([
									config('larapen.core.customizedViewPath') . 'search.inc.posts.template.compact',
									'search.inc.posts.template.compact'
								])
							@else
								@includeFirst([
									config('larapen.core.customizedViewPath') . 'search.inc.posts.template.grid',
									'search.inc.posts.template.grid'
								])
							@endif
							
							<div style="clear: both"></div>
							
							@if (data_get($sectionOptions, 'show_view_more_btn') == '1')
								<div class="mb20 text-center">
									<a href="{{ \App\Helpers\UrlGen::searchWithoutQuery() }}" class="btn btn-default mt10">
										<i class="fa fa-arrow-circle-right"></i> {{ t('View more') }}
									</a>
								</div>
							@endif
						</div>
					</div>
				</div>
				
			</div>
		</div>
	</div>
@endif

@section('after_scripts')
    @parent
@endsection
