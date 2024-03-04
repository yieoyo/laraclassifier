<?php

use App\Helpers\DBTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

try {
	
	/* FILES */
	File::delete(app_path('Http/Controllers/Web/Post/DetailsController.php'));
	File::delete(resource_path('views/post/details.blade.php'));
	File::delete(resource_path('views/post/inc/fields.blade.php'));
	File::delete(resource_path('views/post/inc/fields-values.blade.php'));
	File::delete(resource_path('views/post/inc/pictures-slider.blade.php'));
	File::delete(resource_path('views/post/inc/pictures-slider/bootstrap-carousel.blade.php'));
	File::delete(resource_path('views/post/inc/pictures-slider/bxslider-horizontal.blade.php'));
	File::delete(resource_path('views/post/inc/pictures-slider/bxslider-vertical.blade.php'));
	File::delete(resource_path('views/post/inc/pictures-slider/swiper-horizontal.blade.php'));
	File::delete(resource_path('views/post/inc/pictures-slider/swiper-vertical.blade.php'));
	File::delete(resource_path('views/post/inc/security-tips.blade.php'));
	
	
	
	/* DATABASE */
	
	
	
} catch (\Exception $e) {
	dump($e->getMessage());
	dd('in ' . str_replace(base_path(), '', __FILE__));
}
