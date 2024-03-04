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

namespace App\Helpers\Files\Response;

use Illuminate\Filesystem\FilesystemAdapter;

class ImageResponse
{
	/**
	 * Create response for previewing specified image.
	 * Optionally resize image to specified size.
	 *
	 * @param $disk
	 * @param string|null $filePath
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
	 */
	public static function create($disk, ?string $filePath)
	{
		if (!$disk instanceof FilesystemAdapter) {
			abort(500);
		}
		
		if (empty($filePath) || !$disk->exists($filePath)) {
			abort(404);
		}
		
		$mime = $disk->mimeType($filePath);
		$content = $disk->get($filePath);
		
		return response($content, 200)->header('Content-Type', $mime);
	}
}
