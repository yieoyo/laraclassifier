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

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models;

use Doctrine\DBAL\Schema\Column;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Methods for working with relationships inside select/relationship fields.
|--------------------------------------------------------------------------
*/
trait HasRelationshipFields
{
	public static function isColumnNullable(string $columnName): bool
	{
		$instance = new static(); // Create an instance of the model to be able to get the table name
		
		$database = config('database.connections.' . config('database.default') . '.database');
		$tableName = DB::getTablePrefix() . $instance->getTable();
		
		try {
			$sql = "SELECT IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME='" . $tableName . "'
                    AND COLUMN_NAME='" . $columnName . "'
                    AND table_schema='" . $database . "'";
			$answer = DB::select($sql)[0];
		} catch (\Throwable $e) {
			return $instance->isColumnNullable2($columnName);
		}
		
		return $answer->IS_NULLABLE === 'YES';
	}
	
	public static function isColumnNullable2(string $columnName): bool
	{
		$instance = new static(); // Create an instance of the model to be able to get the table name
		$tableName = DB::getTablePrefix() . $instance->getTable();
		
		// Register the enum and jsonb column type, because Doctrine doesn't support it
		/*
		 * In Doctrine 3.4.x
		 * - DB::connection()->getDoctrineSchemaManager()->getDatabasePlatform() is deprecated
		 *   Use DB::connection()->getDoctrineConnection()->getDatabasePlatform() instead.
		 * - The json column type is supported.
		 * - The jsonb column type is still not supported.
		 * - The json_array column type is no longer supported.
		 */
		DB::connection()->getDoctrineConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
		DB::connection()->getDoctrineConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('jsonb', 'json');
		
		// Retrieve the column metadata
		$column = DB::connection()->getDoctrineColumn($tableName, $columnName);
		
		// Check if the column is nullable
		// $isNullable = ($column instanceof Column && $column->getNotnull() === false);
		return ($column->getNotnull() === false);
	}
}
