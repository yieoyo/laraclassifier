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

namespace App\Observers\Traits;

use App\Helpers\DBTool;
use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

trait LanguageTrait
{
	/**
	 * UPDATING - Set default language (Call this method at last)
	 *
	 * @param $code
	 * @return void
	 */
	public static function setDefaultLanguage($code): void
	{
		// Unset the old default language
		Language::whereIn('active', [0, 1])->update(['default' => 0]);
		
		// Set the new default language
		Language::where('code', $code)->update(['default' => 1]);
		
		// Update the Default App Locale
		self::updateDefaultAppLocale($code);
	}
	
	// PRIVATE METHODS
	
	/**
	 * Update the Default App Locale
	 *
	 * @param $locale
	 * @return void
	 */
	private static function updateDefaultAppLocale($locale): void
	{
		if (!DotenvEditor::keyExists('APP_LOCALE')) {
			DotenvEditor::addEmpty();
		}
		DotenvEditor::setKey('APP_LOCALE', $locale);
		DotenvEditor::save();
	}
	
	/**
	 * Forgetting all DB translations for a specific locale
	 *
	 * @param $locale
	 * @return void
	 */
	protected function forgetAllTranslations($locale): void
	{
		// JSON columns manipulation is only available in:
		// MySQL 5.7 or above & MariaDB 10.2.3 or above
		$jsonMethodsAreAvailable = (
			(!DBTool::isMariaDB() && DBTool::isMySqlMinVersion('5.7'))
			|| (DBTool::isMariaDB() && DBTool::isMySqlMinVersion('10.2.3'))
		);
		if (! $jsonMethodsAreAvailable) {
			return;
		}
		
		$modelClasses = DBTool::getAppModelClasses(translatable: true);
		if (empty($modelClasses)) {
			return;
		}
		
		foreach ($modelClasses as $modelClass) {
			$model = new $modelClass;
			
			// Get the translatable columns
			$columns = method_exists($model, 'getTranslatableAttributes')
				? $model->getTranslatableAttributes()
				: [];
			if (empty($columns)) {
				continue;
			}
			
			$tableName = $model->getTable();
			foreach ($columns as $column) {
				$value = 'JSON_REMOVE(' . $column . ', \'$.' . $locale . '\')';
				// $filter = $column . ' REGEXP \'.+"' . $locale . '":.+\'';
				$filter = $column . ' LIKE \'%"' . $locale . '":%\'';
				
				DB::table($tableName)
					->whereNotNull($column)
					->whereRaw($column . ' != ""')
					->whereRaw($filter)
					->update([$column => DB::raw($value)]);
			}
		}
	}
}
