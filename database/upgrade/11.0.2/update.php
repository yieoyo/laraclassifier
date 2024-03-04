<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

try {
	
	/* FILES */
	File::delete(app_path('Http/Controllers/Web/Search/Traits/CategoryTrait.php'));
	File::delete(app_path('Http/Controllers/Web/Search/Traits/LocationTrait.php'));
	File::delete(app_path('Http/Middleware/InputRequest/ApiCalls.php'));
	File::delete(resource_path('views/account/saved-search.blade.php'));
	
	
	/* DATABASE */
	
	
} catch (\Exception $e) {
	dump($e->getMessage());
	dd('in ' . str_replace(base_path(), '', __FILE__));
}
