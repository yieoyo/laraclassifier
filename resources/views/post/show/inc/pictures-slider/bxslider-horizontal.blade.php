@php
	$titleSlug ??= '';
@endphp
{{-- bxSlider - Horizontal Thumbnails --}}
<div class="gallery-container">
	@if (!empty($price))
		<div class="p-price-tag">{!! $price !!}</div>
	@endif
	<div class="bxslider">
		@forelse($pictures as $key => $image)
			<div class="bx-item">
				{!! imgTag(data_get($image, 'filename'), 'big', ['alt' => $titleSlug . '-big-' . $key]) !!}
			</div>
		@empty
			<div class="bx-item">
				<img src="{{ imgUrl(config('larapen.core.picture.default'), 'big') }}" alt="img" class="default-picture">
			</div>
		@endforelse
	</div>
	<div class="bxslider-pager">
		<div class="bx-gallery-thumbs">
			@forelse($pictures as $key => $image)
				<a class="bx-thumb-item" data-slide-index="{{ $key }}" href="">
					{!! imgTag(data_get($image, 'filename'), 'small', ['alt' => $titleSlug . '-small-' . $key]) !!}
				</a>
			@empty
				<a class="bx-thumb-item" data-slide-index="0" href="">
					<img src="{{ imgUrl(config('larapen.core.picture.default'), 'small') }}" alt="img" class="default-picture">
				</a>
			@endforelse
		</div>
	</div>
</div>

@section('after_styles')
	@parent
	@if (config('lang.direction') == 'rtl')
		<link href="{{ url('assets/plugins/bxslider/jquery.bxslider.rtl.css') }}" rel="stylesheet"/>
	@else
		<link href="{{ url('assets/plugins/bxslider/jquery.bxslider.css') }}" rel="stylesheet"/>
	@endif
	<link href="{{ url('assets/plugins/bxslider/bxslider-custom.css') }}" rel="stylesheet"/>
@endsection
@section('after_scripts')
	@parent
	<script src="{{ url('assets/plugins/bxslider/jquery.bxslider.min.js') }}"></script>
	<script>
		var totalSlides = {{ count((array)$pictures) }};
		
		/* Enable touch events for Mobile Browsers */
		var touchDevice = false;
		if (navigator.userAgent.match(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/)) {
			touchDevice = (totalSlides > 1);
		}
		
		$(document).ready(function () {
			
			/* bxSlider - Main Gallery */
			$('.bxslider').bxSlider({
				touchEnabled: touchDevice,
				speed: 300,
				pagerCustom: '.bxslider-pager',
				adaptiveHeight: true,
				nextText: '{{ t('bxslider.nextText') }}',
				prevText: '{{ t('bxslider.prevText') }}',
				startText: '{{ t('bxslider.startText') }}',
				stopText: '{{ t('bxslider.stopText') }}',
			});
			
			/* bxSlider - Thumbnails Gallery */
			$('.bxslider-pager').addClass('m-3');
			$('.bxslider-pager .bx-thumb-item').unwrap();
			
			/* Full Size Images Gallery */
			$(document).on('mousedown', '.bxslider img', function (e) {
				e.preventDefault();
				
				let currentSrc = $(this).attr('src');
				let imgTitle = "{{ data_get($post, 'title') }}";
				
				let wrapperSelector = '.bxslider img:not(.default-picture)';
				let imgSrcArray = getFullSizeSrcOfAllImg(wrapperSelector, currentSrc);
				if (imgSrcArray === undefined || imgSrcArray.length === 0) {
					return false;
				}
				
				{{-- Load full size pictures slides dynamically --}}
				let swipeboxItems = formatImgSrcArrayForSwipebox(imgSrcArray, imgTitle);
				let swipeboxOptions = {
					hideBarsDelay: (1000 * 60 * 5),
					loopAtEnd: false
				};
				$.swipebox(swipeboxItems, swipeboxOptions);
			});
		});
		
		/* bxSlider - Initiates Responsive Carousel */
		function bxSliderSettings()
		{
			let smSettings = {
				slideWidth: 65,
				minSlides: 1,
				maxSlides: 4,
				slideMargin: 5,
				adaptiveHeight: true,
				pager: false
			};
			let mdSettings = {
				slideWidth: 100,
				minSlides: 1,
				maxSlides: 4,
				slideMargin: 5,
				adaptiveHeight: true,
				pager: false
			};
			let lgSettings = {
				slideWidth: 100,
				minSlides: 3,
				maxSlides: 6,
				pager: false,
				slideMargin: 10,
				adaptiveHeight: true
			};
			
			if ($(window).width() <= 640) {
				return smSettings;
			} else if ($(window).width() > 640 && $(window).width() < 768) {
				return mdSettings;
			} else {
				return lgSettings;
			}
		}
	</script>
@endsection
