<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

namespace App\Http\Controllers\Web\Install\Traits\Install;

use App\Helpers\DBTool;
use App\Http\Controllers\Web\Install\Traits\Install\Db\MigrationsTrait;
use App\Models\City;
use App\Models\Country;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

trait DbTrait
{
	use MigrationsTrait;
	
	/**
	 * STEP 4 - Database Import Submission
	 *
	 * @param $siteInfo
	 * @param $database
	 * @return \Illuminate\Http\RedirectResponse
	 */
	private function submitDatabaseImport($siteInfo, $database)
	{
		// Get PDO connexion
		$pdo = DBTool::getPDOConnexion($database);
		
		// Get database tables prefix
		$tablesPrefix = (!empty($database['prefix'])) ? $database['prefix'] : null;
		
		// Check if the database is not empty
		$rules = [];
		$tables = DBTool::getRawDatabaseTables($pdo, $database['database'], $tablesPrefix);
		if (!empty($tables)) {
			if (!empty($tablesPrefix)) {
				// 1. Drop all old tables
				$this->dropExistingTables($pdo, $tables);
				
				// 2. Check if all tables are dropped (Check if database's tables still exist)
				$tablesExist = false;
				$tables = DBTool::getRawDatabaseTables($pdo, $database['database'], $tablesPrefix);
				if (!empty($tables)) {
					$tablesExist = true;
				}
				
				if ($tablesExist) {
					$rules['can_not_empty_database'] = 'required';
				}
			} else {
				// 1. Invalidate the request
				$rules['database_not_empty'] = 'required';
			}
			
			// 3. (or 2.) Validation
			if (!empty($rules)) {
				$validator = Validator::make(request()->all(), $rules);
				if ($validator->fails()) {
					return redirect()->back()->withErrors($validator)->send();
				}
			}
		}
		
		// 4. 1. Import database schema (Migration)
		$this->runMigrations();
		
		// 4. 2. Check if database tables are created
		if (!$this->isAllModelsTablesExist($pdo, $tablesPrefix)) {
			$rules['can_not_create_database_tables'] = 'required';
			
			$validator = Validator::make(request()->all(), $rules);
			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator)->send();
			}
		}
		
		// 5. 1. Import required data (Seeding)
		$this->runSeeders();
		
		// 5. 2. Import Geonames Default country database
		$this->importGeonamesSql($pdo, $tablesPrefix, $siteInfo['default_country']);
		
		// 5. 3. Check if all required data are imported
		$countCountries = $countCities = 0;
		try {
			$countCountries = DB::table((new Country())->getTable())->count(); // Latest seeder run
			$countCities = DB::table((new City())->getTable())->where('country_code', $siteInfo['default_country'])->count();
		} catch (\Throwable $e) {}
		if ($countCountries <= 0 || $countCities <= 0) {
			$rules['can_not_import_database_data'] = 'required';
			
			$validator = Validator::make(request()->all(), $rules);
			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator)->send();
			}
		}
		
		// 6. Insert & Update the Site Information
		$this->runSiteInfoSeeder($siteInfo);
		
		// Close PDO connexion
		DBTool::closePDOConnexion($pdo);
	}
	
	/**
	 * Drop All Existing Tables
	 *
	 * @param \PDO $pdo
	 * @param array|null $tables
	 * @return void
	 */
	private function dropExistingTables(\PDO $pdo, ?array $tables)
	{
		if (empty($tables)) {
			return;
		}
		
		// Try 4 times
		$try = 5;
		while ($try > 0) {
			$this->flushTables($pdo);
			
			try {
				// Extend query max setting
				$pdo->exec('SET group_concat_max_len = 9999999;');
				
				// Drop all tables
				$pdo->exec('SET foreign_key_checks = 0;');
				foreach ($tables as $table) {
					if (DBTool::tableExists($pdo, $table)) {
						$pdo->exec('DROP TABLE ' . $table . ';');
					}
				}
				$pdo->exec('SET foreign_key_checks = 1;');
				
				$try--;
			} catch (\Throwable $e) {
				dd($e->getMessage());
			}
			
			$this->flushTables($pdo);
		}
	}
	
	/**
	 * Flush Tables
	 *
	 * [ MySQL 5.6 | 5.7 ]
	 * - Closes all open tables, forces all tables in use to be closed, and flushes the query cache and prepared statement cache.
	 * - FLUSH TABLES also removes all query results from the query cache, like the RESET QUERY CACHE statement.
	 *
	 *   For information about query caching and prepared statement caching, see:
	 * - Section 8.10.3, “The MySQL Query Cache”: https://dev.mysql.com/doc/refman/5.7/en/query-cache.html
	 * - and Section 8.10.4, “Caching of Prepared Statements and Stored Programs”: https://dev.mysql.com/doc/refman/5.7/en/statement-caching.html
	 *
	 * [ MySQL 8.0 ]
	 * - Closes all open tables, forces all tables in use to be closed, and flushes the prepared statement cache.
	 * - This operation requires the FLUSH_TABLES or RELOAD privilege.
	 * - More info: https://dev.mysql.com/doc/refman/8.0/en/flush.html
	 *
	 * How MySQL Handles FLUSH TABLES: https://dev.mysql.com/doc/internals/en/flush-tables.html
	 *
	 * [ MariaDB 10.4.8 ]
	 * - The purpose of FLUSH TABLES is to clean up the open table cache and table definition cache from not in use tables.
	 *   This frees up memory and file descriptors. Normally this is not needed as the caches works on a FIFO bases,
	 *   but can be useful if the server seams to use up to much memory for some reason.
	 * - More info: https://mariadb.com/kb/en/flush/#the-different-usage-of-flush-tables
	 *
	 * @param \PDO $pdo
	 * @return void
	 */
	private function flushTables(\PDO $pdo): void
	{
		try {
			$pdo->exec('FLUSH TABLES;');
		} catch (\Throwable $e) {
			dd('ERROR: No privilege to run: "FLUSH TABLES;" - ' . $e->getMessage());
		}
	}
	
	/**
	 * Import the Default Country Data from the Geonames SQL Files
	 *
	 * @param \PDO $pdo
	 * @param $tablesPrefix
	 * @param $defaultCountryCode
	 * @return bool
	 */
	private function importGeonamesSql(\PDO $pdo, $tablesPrefix, $defaultCountryCode)
	{
		// Default Country SQL file
		$filename = 'database/geonames/countries/' . strtolower($defaultCountryCode) . '.sql';
		$filePath = storage_path($filename);
		
		// Import the SQL file
		$res = DBTool::importSqlFile($pdo, $filePath, $tablesPrefix);
		if ($res === false) {
			dd('ERROR');
		}
		
		return $res;
	}
	
	/**
	 * Check if all models' tables exist
	 *
	 * @param $pdo
	 * @param null $tablesPrefix
	 * @return bool
	 */
	private function isAllModelsTablesExist($pdo, $tablesPrefix = null): bool
	{
		$isAllTablesExist = true;
		try {
			// Check if all database tables exist
			$modelFiles = DBTool::getAppModelFiles();
			
			if (!empty($modelFiles)) {
				foreach ($modelFiles as $filePath) {
					$table = DBTool::getModelTableName($filePath);
					
					if (empty($table)) {
						continue;
					}
					
					if (!DBTool::tableExists($pdo, $table, $tablesPrefix)) {
						$isAllTablesExist = false;
					}
				}
			}
		} catch (\PDOException|\Throwable $e) {
			$isAllTablesExist = false;
		}
		
		return $isAllTablesExist;
	}
	
	/**
	 * Setup ACL system
	 */
	private function setupAclSystem(): void
	{
		// Check & Fix the default Permissions
		if (!Permission::checkDefaultPermissions()) {
			Permission::resetDefaultPermissions();
		}
	}
}
