<?php

use App\Helpers\DBTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

try {
	
	/* FILES */
	$sourceFile = public_path('images/user.png');
	$targetFile = storage_path('app/public/app/default/user.png');
	if (File::exists($sourceFile)) {
		if (File::exists($targetFile)) {
			File::delete($sourceFile);
		} else {
			File::move($sourceFile, $targetFile);
		}
	}
	
	
	
	/* DATABASE */
	$tablePrefix = DB::getTablePrefix();
	
	$sql = "ALTER TABLE `" . $tablePrefix . "blacklist`" . "\n"
		. "MODIFY COLUMN `type` enum('domain','email','phone','ip','word')" . "\n"
		. "COLLATE utf8mb4_unicode_ci" . "\n"
		. "DEFAULT NULL;" . "\n";
	DB::statement($sql);
	
	
} catch (\Exception $e) {
	dump($e->getMessage());
	dd('in ' . str_replace(base_path(), '', __FILE__));
}
