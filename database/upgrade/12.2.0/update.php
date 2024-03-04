<?php

use App\Helpers\DBTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

try {
	
	/* FILES */
	File::delete(app_path('Http/Controllers/Api/Post/CreateOrEdit/Traits/CategoriesTrait.php'));
	File::delete(app_path('Http/Controllers/Web/Ajax/LocationController.php'));
	File::delete(app_path('Http/Controllers/Web/Post/Traits/CustomFieldTrait.php'));
	File::delete(public_path('assets/js/app/d.select.category.js'));
	File::delete(public_path('assets/js/app/load.cities.js'));
	File::delete(resource_path('views/post/createOrEdit/inc/category/parent.blade.php'));
	
	
	
	/* DATABASE */
	if (Schema::hasColumn('countries', 'admin_field_active')) {
		Schema::table('countries', function (Blueprint $table) {
			$table->dropColumn('admin_field_active');
		});
	}
	
	
} catch (\Exception $e) {
	dump($e->getMessage());
	dd('in ' . str_replace(base_path(), '', __FILE__));
}
