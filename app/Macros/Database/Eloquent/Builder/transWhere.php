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

use App\Helpers\DBTool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\SpatieTranslatable\HasTranslations;

/*
 * NOTE: Don't rename 'transWhere' to 'whereTrans' to prevent overriding the eloquent where clause.
 * For example, in eloquent ->whereTrans('foo') invokes ->where('trans', '=', 'foo')
 */
Builder::macro('transWhere', function ($column, $operator = null, $value = null, $boolean = 'and') {
	
	if (!(isset($this->query) && $this->query instanceof QueryBuilder)) {
		return $this;
	}
	
	if ($column instanceof \Closure) {
		$this->query->where($column, $operator, $value, $boolean);
		
		return $this;
	}
	
	$isTranslatableModel = (
		isset($this->model)
		&& in_array(HasTranslations::class, class_uses($this->model))
		&& in_array($column, $this->model->translatable)
	);
	if (!$isTranslatableModel) {
		$this->query->where($column, $operator, $value, $boolean);
		
		return $this;
	}
	
	if (func_num_args() == 2 && empty($value)) {
		$value = $operator;
	}
	
	$locale = $locale ?? app()->getLocale();
	$masterLocale = config('translatable.fallback_locale') ?? config('app.fallback_locale');
	
	// Escaping Quote
	$value = str_replace(['\''], ['\\\''], $value);
	
	// JSON columns manipulation is only available in:
	// MySQL 5.7 or above & MariaDB 10.2.3 or above
	$jsonMethodsAreAvailable = (
		(!DBTool::isMariaDB() && DBTool::isMySqlMinVersion('5.7'))
		|| (DBTool::isMariaDB() && DBTool::isMySqlMinVersion('10.2.3'))
	);
	if ($jsonMethodsAreAvailable) {
		
		$this->query->where(function ($query) use ($column, $locale, $value, $masterLocale) {
			$jsonColumn = jsonExtract($column, $locale);
			$jsonColumn = 'LOWER(' . $jsonColumn . ')';
			$jsonColumn = 'BINARY ' . $jsonColumn;
			
			$value = 'LOWER(\'' . $value . '\')';
			$value = 'BINARY ' . $value;
			
			$query->whereRaw($jsonColumn . ' LIKE ' . $value);
			
			if (!empty($masterLocale) && $locale != $masterLocale) {
				$jsonColumn = jsonExtract($column, $masterLocale);
				$jsonColumn = 'LOWER(' . $jsonColumn . ')';
				$jsonColumn = 'BINARY ' . $jsonColumn;
				
				$query->orWhereRaw($jsonColumn . ' LIKE ' . $value);
			}
		});
		
	} else {
		
		// $value = trim(json_encode($value), '"');
		
		if (!str_starts_with($value, '%')) {
			$value = '%' . ltrim($value, '%');
		}
		if (!str_ends_with($value, '%')) {
			$value = rtrim($value, '%') . '%';
		}
		
		$this->query->where($column, 'LIKE', $value, $boolean);
		
	}
	
	return $this;
});

// orWhere ...
// @todo: The following macro functions need to be tested again

Builder::macro('orTransWhere', function ($column, $operator = null, $value = null) {
	
	if (!(isset($this->query) && $this->query instanceof QueryBuilder)) {
		return $this;
	}
	
	if ($column instanceof \Closure) {
		$this->query->orWhere($column, $operator, $value);
		
		return $this;
	}
	
	$isTranslatableModel = (
		isset($this->model)
		&& in_array(HasTranslations::class, class_uses($this->model))
		&& in_array($column, $this->model->translatable)
	);
	if (!$isTranslatableModel) {
		$this->query->orWhere($column, $operator, $value);
		
		return $this;
	}
	
	if (func_num_args() == 2 && empty($value)) {
		$value = $operator;
	}
	
	$locale = $locale ?? app()->getLocale();
	$masterLocale = config('translatable.fallback_locale') ?? config('app.fallback_locale');
	
	// Escaping Quote
	$value = str_replace(['\''], ['\\\''], $value);
	
	// JSON columns manipulation is only available in:
	// MySQL 5.7 or above & MariaDB 10.2.3 or above
	$jsonMethodsAreAvailable = (
		(!DBTool::isMariaDB() && DBTool::isMySqlMinVersion('5.7'))
		|| (DBTool::isMariaDB() && DBTool::isMySqlMinVersion('10.2.3'))
	);
	if ($jsonMethodsAreAvailable) {
		
		$this->query->orWhere(function ($query) use ($column, $locale, $value, $masterLocale) {
			$jsonColumn = jsonExtract($column, $locale);
			$jsonColumn = 'LOWER(' . $jsonColumn . ')';
			$jsonColumn = 'BINARY ' . $jsonColumn;
			
			$value = 'LOWER(\'' . $value . '\')';
			$value = 'BINARY ' . $value;
			
			$query->whereRaw($jsonColumn . ' LIKE ' . $value);
			
			if (!empty($masterLocale) && $locale != $masterLocale) {
				$jsonColumn = jsonExtract($column, $masterLocale);
				$jsonColumn = 'LOWER(' . $jsonColumn . ')';
				$jsonColumn = 'BINARY ' . $jsonColumn;
				
				$query->orWhereRaw($jsonColumn . ' LIKE ' . $value);
			}
		});
		
	} else {
		
		// $value = trim(json_encode($value), '"');
		
		if (!str_starts_with($value, '%')) {
			$value = '%' . ltrim($value, '%');
		}
		if (!str_ends_with($value, '%')) {
			$value = rtrim($value, '%') . '%';
		}
		
		$this->query->orWhere($column, 'LIKE', $value);
		
	}
	
	return $this;
});
