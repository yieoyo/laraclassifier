@php
	$titleSlug ??= '';
@endphp
{{-- Swiper - Horizontal Thumbnails --}}
<div class="gallery-container">
	@if (!empty($price))
		<div class="p-price-tag">{!! $price !!}</div>
	@endif
	<div class="swiper main-gallery">
		<div class="swiper-wrapper">
			@forelse($pictures as $key => $image)
				<div class="swiper-slide">
					{!! imgTag(data_get($image, 'filename'), 'big', ['alt' => $titleSlug . '-big-' . $key]) !!}
				</div>
			@empty
				<div class="swiper-slide">
					<img src="{{ imgUrl(config('larapen.core.picture.default'), 'big') }}" alt="img" class="default-picture">
				</div>
			@endforelse
		</div>
		<div class="swiper-button-next"></div>
		<div class="swiper-button-prev"></div>
	</div>
	<div class="swiper thumbs-gallery">
		<div class="swiper-wrapper">
			@forelse($pictures as $key => $image)
				<div class="swiper-slide">
					{!! imgTag(data_get($image, 'filename'), 'small', ['alt' => $titleSlug . '-small-' . $key]) !!}
				</div>
			@empty
				<div class="swiper-slide">
					<img src="{{ imgUrl(config('larapen.core.picture.default'), 'small') }}" alt="img" class="default-picture">
				</div>
			@endforelse
		</div>
	</div>
</div>

@section('after_styles')
	@parent
	<link href="{{ url('assets/plugins/swiper/7.4.1/swiper-bundle.min.css') }}" rel="stylesheet"/>
	<link href="{{ url('assets/plugins/swiper/7.4.1/swiper-horizontal-thumbs.css') }}" rel="stylesheet"/>
	@if (config('lang.direction') == 'rtl')
		<link href="{{ url('assets/plugins/swiper/7.4.1/swiper-horizontal-thumbs-rtl.css') }}" rel="stylesheet"/>
	@endif
@endsection
@section('after_scripts')
	@parent
	<script src="{{ url('assets/plugins/swiper/7.4.1/swiper-bundle.min.js') }}"></script>
	<script>
		$(document).ready(function () {
			let thumbsGalleryOptions = {
				slidesPerView: 2,
				spaceBetween: 5,
				freeMode: true,
				watchSlidesProgress: true,
				/* Responsive breakpoints */
				breakpoints: {
					/* when window width is >= 320px */
					320: {
						slidesPerView: 3
					},
					/* when window width is >= 576px */
					576: {
						slidesPerView: 4
					},
					/* when window width is >= 768px */
					768: {
						slidesPerView: 5
					},
					/* when window width is >= 992px */
					992: {
						slidesPerView: 6
					},
				},
				centerInsufficientSlides: true,
				direction: 'horizontal',
			};
			let thumbsGallery = new Swiper('.thumbs-gallery', thumbsGalleryOptions);
			
			let mainGalleryOptions = {
				speed: 300,
				loop: true,
				spaceBetween: 10,
				navigation: {
					nextEl: '.swiper-button-next',
					prevEl: '.swiper-button-prev',
				},
				thumbs: {
					swiper: thumbsGallery,
				},
				autoHeight: true,
				grabCursor: true,
			};
			let mainGallery = new Swiper('.main-gallery', mainGalleryOptions);
			
			mainGallery.on('click', function (swiper, event) {
				/* console.log(swiper); */
				if (typeof swiper.clickedSlide === 'undefined') {
					return false;
				}
				
				let imgEl = swiper.clickedSlide.querySelector('img');
				if (typeof imgEl === 'undefined' || typeof imgEl.src === 'undefined') {
					return false;
				}
				
				let currentSrc = imgEl.src;
				let imgTitle = "{{ data_get($post, 'title') }}";
				
				let wrapperSelector = '.main-gallery .swiper-slide:not(.swiper-slide-duplicate) img:not(.default-picture)';
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
	</script>
@endsection
