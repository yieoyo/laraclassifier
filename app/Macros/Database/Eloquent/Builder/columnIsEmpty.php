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

/*
 * NOTE:
 * Don't rename 'columnIsEmpty' to 'whereEmpty' to prevent overriding the eloquent where clause.
 * For example, in eloquent ->whereEmpty('foo') invokes ->where('empty', '=', 'foo')
 */
/*
 * WARNING:
 * Use 'whereNull' instead for date (date, datetime or timestamp) columns,
 * since they don't support comparison with empty character.
 */
Builder::macro('columnIsEmpty', function (string $column) {
	
	if (!(isset($this->query) && $this->query instanceof QueryBuilder)) {
		return $this;
	}
	
	$this->query->where(function ($query) use ($column) {
		$query->where($column, '')->orWhere($column, 0)->orWhereNull($column);
	});
	
	return $this;
});

/*
 * WARNING:
 * Use 'whereNotNull' instead for date (date, datetime or timestamp) columns,
 * since they don't support comparison with empty character.
 */
Builder::macro('columnIsNotEmpty', function (string $column) {
	
	if (!(isset($this->query) && $this->query instanceof QueryBuilder)) {
		return $this;
	}
	
	$this->query->where(function ($query) use ($column) {
		$query->where($column, '!=', '')->where($column, '!=', 0)->whereNotNull($column);
	});
	
	return $this;
});

// orWhere ...
// @todo: The following macro functions need to be tested again

Builder::macro('orColumnIsEmpty', function (string $column) {
	
	if (!(isset($this->query) && $this->query instanceof QueryBuilder)) {
		return $this;
	}
	
	$this->query->orWhere(fn ($query) => $query->columnIsEmpty($column));
	
	return $this;
});

Builder::macro('orColumnIsNotEmpty', function (string $column) {
	
	if (!(isset($this->query) && $this->query instanceof QueryBuilder)) {
		return $this;
	}
	
	$this->query->orWhere(fn ($query) => $query->columnIsNotEmpty($column));
	
	return $this;
});
