<?php

use App\Helpers\DBTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

// ===| FILES |===
try {
	
	File::delete(app_path('Http/Controllers/Api/Base/LocalizationTrait.php'));
	File::delete(app_path('Http/Controllers/Web/Public/Traits/LocalizationTrait.php'));
	
	File::delete(app_path('Models/Setting/GeoLocationSetting.php'));
	File::delete(app_path('Observers/Traits/Setting/GeoLocationTrait.php'));
	File::delete(app_path('Providers/AppService/ConfigTrait/GeolocationConfig.php'));
	if (File::exists(storage_path('framework/plugins/domainmapping'))) {
		File::delete(plugin_path('domainmapping', 'app/Models/Setting/GeoLocationSetting.php'));
	}
	
	File::delete(config_path('currency-symbols.php'));
	File::delete(config_path('languages.php'));
	File::delete(config_path('locales.php'));
	File::delete(config_path('time-zones.php'));
	File::delete(config_path('tlds.php'));
	
	File::deleteDirectory(public_path('images/flags/16/'));
	File::deleteDirectory(public_path('images/flags/24/'));
	File::deleteDirectory(public_path('images/flags/32/'));
	File::deleteDirectory(public_path('images/flags/48/'));
	File::deleteDirectory(public_path('images/flags/64/'));
	
} catch (\Exception $e) {
}

// ===| DATABASE |===
try {
	
	include_once __DIR__ . '/../../../app/Helpers/Functions/migration.php';
	
	// languages
	checkAndDropIndex('languages', 'abbr');
	
	if (
		Schema::hasColumn('languages', 'abbr')
		&& !Schema::hasColumn('languages', 'code')
	) {
		Schema::table('languages', function ($table) {
			$table->renameColumn('abbr', 'code');
		});
	}
	
	if (Schema::hasColumn('languages', 'code')) {
		Schema::table('languages', function (Blueprint $table) {
			$tableName = DBTool::table('languages');
			
			// Create indexes
			$indexes = ['code'];
			foreach ($indexes as $index) {
				$indexName = $tableName . '_' . $index . '_index';
				$sql = 'SHOW KEYS FROM ' . $tableName . ' WHERE Key_name="' . $indexName . '"';
				$keyExists = DB::select($sql);
				if (!$keyExists) {
					$table->index([$index], $indexName);
				}
			}
		});
	}
	
	if (Schema::hasColumn('languages', 'app_name')) {
		Schema::table('languages', function (Blueprint $table) {
			$table->dropColumn('app_name');
		});
	}
	
	// settings
	$setting = \App\Models\Setting::where('key', 'geo_location')->first();
	if (!empty($setting)) {
		$setting->key = 'localization';
		$setting->name = 'Localization';
		$setting->description = 'Localization Configuration';
		$setting->save();
	}
	
} catch (Exception $e) {
	
	dump($e->getMessage());
	dd('in ' . str_replace(base_path(), '', __FILE__));
	
}
