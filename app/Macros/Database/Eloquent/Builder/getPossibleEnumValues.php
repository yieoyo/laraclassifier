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

namespace App\Macros\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

Builder::macro('getPossibleEnumValues', function (string $column): array {
	
	$queryBuilder = (isset($this->query) && $this->query instanceof QueryBuilder)
		? $this->query
		: $this;
	
	if (!$queryBuilder instanceof QueryBuilder) {
		return [];
	}
	
	$table = DB::getTablePrefix() . $queryBuilder->from;
	$connectionName = $queryBuilder->getConnection()->getName();
	
	try {
		$sql = 'SHOW COLUMNS FROM ' . $table . ' WHERE Field = "' . $column . '"';
		$type = DB::connection($connectionName)->select($sql)[0]->Type;
	} catch (\Throwable $e) {
		$type = '';
	}
	
	$enum = [];
	
	if (!empty($type)) {
		preg_match('/^enum\((.*)\)$/', $type, $matches);
		$exploded = explode(',', $matches[1]);
		foreach ($exploded as $value) {
			$enum[] = trim($value, "'");
		}
	}
	
	return $enum;
});

Builder::macro('getEnumValuesAsAssocArray', function (string $column): array {
	
	$enumValues = $this->getPossibleEnumValues($column);
	
	$array = array_flip($enumValues);
	
	foreach ($array as $key => $value) {
		$array[$key] = $key;
	}
	
	return $array;
});
