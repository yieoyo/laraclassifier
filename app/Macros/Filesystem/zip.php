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

namespace App\Macros\Filesystem;

use Illuminate\Filesystem\Filesystem;

/**
 * Zip a directory and its contents
 *
 * Usage: File::zipDirectory($sourceDir, $zipFile);
 *
 * @param string $zipFile
 * @param string $extractTo
 */
Filesystem::macro('zipDirectory', function ($sourceDir, $zipFile) {
	return zip_directory($sourceDir, $zipFile);
});

/**
 * Extract a zip file
 *
 * Usage: File::extractZip($zipFile, $extractTo);
 *
 * @param string $zipFile
 * @param string $extractTo
 */
Filesystem::macro('extractZip', function ($zipFile, $extractTo) {
	return extract_zip($zipFile, $extractTo);
});
