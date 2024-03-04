@php
	$titleSlug ??= '';
@endphp
{{-- bxSlider - Horizontal Thumbnails --}}
<div id="picturesCarouselIndicators" class="gallery-container carousel carousel-dark slide" data-bs-ride="carousel">
	<div class="carousel-indicators">
		@forelse($pictures as $key => $image)
			@php
				$activeSlideAttr = '';
				if ($loop->first) {
					$activeSlideAttr = ' class="active" aria-current="true"';
				}
			@endphp
			<button type="button"
					data-bs-target="#picturesCarouselIndicators"
					data-bs-slide-to="{{ $key }}"{!! $activeSlideAttr !!}
					aria-label="Picture {{ $key }}"
			></button>
		@empty
			<button type="button"
					data-bs-target="#picturesCarouselIndicators"
					data-bs-slide-to="{{ $key }}"
					class="active"
					aria-current="true"
					aria-label="Picture {{ $key }}"
			></button>
		@endforelse
	</div>
	@if (!empty($price))
		<div class="p-price-tag">{!! $price !!}</div>
	@endif
	<div class="carousel-inner">
		@forelse($pictures as $key => $image)
			@php
				$activeItemClass = '';
				if ($loop->first) {
					$activeItemClass = ' active';
				}
			@endphp
			<div class="carousel-item{{ $activeItemClass }}">
				@php
					$picAttr = [
						'alt'   => $titleSlug . '-big-' . $key,
						'class' => 'd-block',
					];
				@endphp
				{!! imgTag(data_get($image, 'filename'), 'big', $picAttr) !!}
			</div>
		@empty
			<div class="carousel-item active">
				<img src="{{ imgUrl(config('larapen.core.picture.default'), 'big') }}" alt="img" class="d-block w-100 default-picture">
			</div>
		@endforelse
	</div>
	<button class="carousel-control-prev" type="button" data-bs-target="#picturesCarouselIndicators" data-bs-slide="prev">
		<span class="carousel-control-prev-icon" aria-hidden="true"></span>
		<span class="visually-hidden">{{ t('Previous') }}</span>
	</button>
	<button class="carousel-control-next" type="button" data-bs-target="#picturesCarouselIndicators" data-bs-slide="next">
		<span class="carousel-control-next-icon" aria-hidden="true"></span>
		<span class="visually-hidden">{{ t('Next') }}</span>
	</button>
</div>

@section('after_styles')
	@parent
	<style>
		.gallery-container {
			display: block;
			width: 100%;
			height: auto;
			position: relative;
		}
		
		.carousel-inner {
			width: 100%;
			height: 100%;
			margin-left: auto;
			margin-right: auto;
			
			/* Apply shadow for the main gallery */
			-moz-box-shadow: 0 0 5px #ccc;
			-webkit-box-shadow: 0 0 5px #ccc;
			box-shadow: 0 0 5px #ccc;
			border: 5px solid #fff;
			background: #fff;
			border-radius: 6px;
			
			/* Bottom spacer */
			margin-bottom: 10px;
		}
		
		.carousel-inner img {
			margin: auto;
			border-radius: 6px;
			cursor: pointer;
		}
	</style>
@endsection
@section('after_scripts')
	@parent
	<script>
		$(document).ready(function () {
			
			let picturesCarouselIndicatorsEl = document.querySelector('#picturesCarouselIndicators');
			let carousel = new bootstrap.Carousel(picturesCarouselIndicatorsEl, {
				interval: false,
				ride: false
			});
			
			/* Full Size Images Gallery */
			$(document).on('mousedown', '.carousel-item img', function (e) {
				e.preventDefault();
				
				let currentSrc = $(this).attr('src');
				let imgTitle = "{{ data_get($post, 'title') }}";
				
				let wrapperSelector = '.carousel-item img:not(.default-picture)';
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
