<?php

use App\Helpers\DBTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

// ===| FILES |===

// ===| DATABASE |===
try {
	
	include_once __DIR__ . '/../../../app/Helpers/Functions/migration.php';
	
	// posts
	if (Schema::hasColumn('posts', 'ip_addr')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->string('ip_addr', 50)->nullable()->comment('IP address of creation')->change();
		});
	}
	if (
		Schema::hasColumn('posts', 'ip_addr')
		&& !Schema::hasColumn('posts', 'create_from_ip')
	) {
		Schema::table('posts', function (Blueprint $table) {
			$table->renameColumn('ip_addr', 'create_from_ip');
		});
	}
	if (
		!Schema::hasColumn('posts', 'latest_update_ip')
		&& Schema::hasColumn('posts', 'create_from_ip')
	) {
		Schema::table('posts', function (Blueprint $table) {
			$table->string('latest_update_ip', 50)->nullable()->comment('Latest update IP address')->after('create_from_ip');
		});
	}
	
	// users
	if (Schema::hasColumn('users', 'ip_addr')) {
		Schema::table('users', function (Blueprint $table) {
			$table->string('ip_addr', 50)->nullable()->comment('IP address of creation')->change();
		});
	}
	if (
		Schema::hasColumn('users', 'ip_addr')
		&& !Schema::hasColumn('users', 'create_from_ip')
	) {
		Schema::table('users', function (Blueprint $table) {
			$table->renameColumn('ip_addr', 'create_from_ip');
		});
	}
	if (
		!Schema::hasColumn('users', 'latest_update_ip')
		&& Schema::hasColumn('users', 'create_from_ip')
	) {
		Schema::table('users', function (Blueprint $table) {
			$table->string('latest_update_ip', 50)->nullable()->comment('Latest update IP address')->after('create_from_ip');
		});
	}
	
} catch (Exception $e) {
	
	dump($e->getMessage());
	dd('in ' . str_replace(base_path(), '', __FILE__));
	
}
