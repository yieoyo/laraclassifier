@php
	$post ??= [];
	$titleSlug = str(data_get($post, 'title'))->slug();
	
	$price = data_get($post, 'price_formatted');
	
	$picturesSliderPath = 'post.show.inc.pictures-slider.';
	$defaultPicturesSlider = 'swiper-horizontal';
	$picturesSlider = $picturesSliderPath . config('settings.single.pictures_slider', $defaultPicturesSlider);
@endphp

@if (view()->exists($picturesSlider))
	@includeFirst([config('larapen.core.customizedViewPath') . $picturesSlider, $picturesSlider])
@else
	@php
		$defaultPicturesSlider = $picturesSliderPath . $defaultPicturesSlider;
	@endphp
	@if (view()->exists($defaultPicturesSlider))
		@includeFirst([config('larapen.core.customizedViewPath') . $defaultPicturesSlider, $defaultPicturesSlider])
	@endif
@endif

@section('after_styles')
	@parent
	<link href="{{ url('assets/plugins/swipebox/1.5.2/css/swipebox.css') }}" rel="stylesheet"/>
	@if (config('lang.direction') == 'rtl')
		<style>
			html.swipebox-html {
				overflow: hidden !important;
			}
			html.swipebox-html #swipebox-overlay {
				direction: ltr;
			}
		</style>
	@endif
@endsection
@section('after_scripts')
	@parent
	<script src="{{ url('assets/plugins/swipebox/1.5.2/js/jquery.swipebox.js') }}"></script>
	<script>
		$(document).ready(function () {
			
			let documentBody = $(document.body);
			
			{{-- Navigate to the Swipebox next img when clicking on the current img --}}
			documentBody.on('click touchend', '#swipebox-slider .current img', function() {
				let clickedEl = $(this).get(0);
				if (clickedEl === undefined || clickedEl.nodeName === undefined) {
					return false;
				}
				
				if (strToLower(clickedEl.nodeName) === 'img') {
					$('#swipebox-next').click();
				}
				
				return false;
			});
			
			{{-- Closing the Swipebox modal on click on the background --}}
			documentBody.on('click touchend', '#swipebox-slider .current', function() {
				let clickedEl = $(this).get(0);
				if (clickedEl === undefined || clickedEl.nodeName === undefined) {
					return false;
				}
				
				if (strToLower(clickedEl.nodeName) !== 'img') {
					$('#swipebox-close').click();
				}
			});
			
		});
		
		/**
		 * Get the swipebox items
		 *
		 * @param imgSrcArray
		 * @param title
		 * @returns {*}
		 */
		function formatImgSrcArrayForSwipebox(imgSrcArray, title = 'Title') {
			return map(imgSrcArray, function(imgSrc, index) {
				return { href:imgSrc, title:title };
			});
		}
		
		/**
		 * Get full size src of all pictures
		 *
		 * @param wrapperSelector
		 * @param currentSrc
		 * @returns {*[]}
		 */
		function getFullSizeSrcOfAllImg(wrapperSelector, currentSrc) {
			let allEl = document.querySelectorAll(wrapperSelector);
			
			let imgSrcArray = [getFullSizeSrc(currentSrc)];
			
			forEach(allEl, function(el, index) {
				if (el.src !== currentSrc) {
					imgSrcArray.push(getFullSizeSrc(el.src));
				}
			});
			
			return imgSrcArray;
		}
		
		/**
		 * Get the current picture's full size source
		 *
		 * @param imgSrc
		 * @returns {*}
		 */
		function getFullSizeSrc(imgSrc) {
			let regex = /thumb-(\d+)x(\d+)-/i;
			
			return imgSrc.replace(regex, '');
		}
	</script>
@endsection
