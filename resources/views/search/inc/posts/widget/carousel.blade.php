@php
	$widget ??= [];
	$posts = (array)data_get($widget, 'posts');
	$totalPosts = (int)data_get($widget, 'totalPosts', 0);
	
	$sectionOptions ??= [];
	$hideOnMobile = (data_get($sectionOptions, 'hide_on_mobile') == '1') ? ' hidden-sm' : '';
	$carouselEl = '_' . createRandomString(6);
	
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
				<div class="col-xl-12 box-title">
					<div class="inner">
						<h2>
							<span class="title-3">{!! data_get($widget, 'title') !!}</span>
							<a href="{{ data_get($widget, 'link') }}" class="sell-your-item">
								{{ t('View more') }} <i class="fas fa-bars"></i>
							</a>
						</h2>
					</div>
				</div>
		
				<div style="clear: both"></div>
		
				<div class="relative content featured-list-row clearfix">
					
					<div class="large-12 columns">
						<div class="no-margin featured-list-slider {{ $carouselEl }} owl-carousel owl-theme">
							@foreach($posts as $key => $post)
								<div class="item">
									<a href="{{ \App\Helpers\UrlGen::post($post) }}">
										<span class="item-carousel-thumb">
											<span class="photo-count">
												<i class="fa fa-camera"></i> {{ data_get($post, 'count_pictures') }}
											</span>
											@php
												echo imgTag(data_get($post, 'picture.filename'), 'medium', [
														'style' => 'border: 1px solid #e7e7e7; margin-top: 2px;',
														'alt'   => data_get($post, 'title')
													]);
											@endphp
										</span>
										<span class="item-name">{{ str(data_get($post, 'title'))->limit(70) }}</span>
										
										@if (config('plugins.reviews.installed'))
											@if (view()->exists('reviews::ratings-list'))
												@include('reviews::ratings-list')
											@endif
										@endif
										
										<span class="price">
											{!! data_get($post, 'price_formatted') !!}
										</span>
									</a>
								</div>
							@endforeach
						</div>
					</div>
		
				</div>
			</div>
		</div>
	</div>
@endif

@section('after_style')
	@parent
@endsection

@section('after_scripts')
	@parent
	<script>
		{{-- Check if RTL or LTR --}}
		let rtlIsEnabled = false;
		if ($('html').attr('dir') === 'rtl') {
			rtlIsEnabled = true;
		}
		
		{{-- Carousel Parameters --}}
		let carouselItems = {{ $totalPosts ?? 0 }};
		let carouselAutoplay = {{ data_get($sectionOptions, 'autoplay') ?? 'false' }};
		let carouselAutoplayTimeout = {{ (int)(data_get($sectionOptions, 'autoplay_timeout') ?? 1500) }};
		let carouselLang = {
			'navText': {
				'prev': "{{ t('prev') }}",
				'next': "{{ t('next') }}"
			}
		};
		
		{{-- Featured Listings Carousel --}}
		let carouselObject = $('.featured-list-slider.{{ $carouselEl }}');
		let responsiveObject = {
			0: {
				items: 1,
				nav: true
			},
			576: {
				items: 2,
				nav: false
			},
			768: {
				items: 3,
				nav: false
			},
			992: {
				items: 5,
				nav: false,
				loop: (carouselItems > 5)
			}
		};
		carouselObject.owlCarousel({
			rtl: rtlIsEnabled,
			nav: false,
			navText: [carouselLang.navText.prev, carouselLang.navText.next],
			loop: true,
			responsiveClass: true,
			responsive: responsiveObject,
			autoWidth: true,
			autoplay: carouselAutoplay,
			autoplayTimeout: carouselAutoplayTimeout,
			autoplayHoverPause: true
		});
	</script>
@endsection
