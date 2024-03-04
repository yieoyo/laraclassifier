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

namespace App\Http\Controllers\Api\Picture;

use App\Helpers\Files\Upload;
use App\Http\Requests\Request;
use App\Models\Picture;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;

trait SingleStepPictures
{
	/**
	 * @param $postId
	 * @param \App\Http\Requests\Request $request
	 * @return array
	 */
	public function singleStepPicturesStore($postId, Request $request): array
	{
		$pictures = [];
		
		// Get normal files uploaded
		$files = (array)$request->file('pictures');
		
		// If files not found, get manually added files uploaded
		if (empty($files)) {
			$files = (array)$request->files->get('pictures');
		}
		
		// If files not found again, return an empty array
		if (empty($files)) {
			return $pictures;
		}
		
		// Get pictures' post
		$post = Post::query()
			->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
			->where('id', $postId)
			->first();
		
		if (empty($post)) {
			return $pictures;
		}
		
		// Save all pictures
		$i = 0;
		foreach ($files as $key => $file) {
			if (empty($file)) {
				continue;
			}
			
			$picturePosition = $i;
			if (in_array($request->method(), ['PUT', 'PATCH', 'UPDATE'])) {
				// Delete old file if new file has uploaded
				// Check if current Listing have a pictures
				$possiblePictures = Picture::query()->where('post_id', $post->id)->where('id', $key);
				if ($possiblePictures->count() > 0) {
					$picture = $possiblePictures->first();
					$picturePosition = $picture->position;
					$picture->delete();
				}
			}
			
			// Save Post's Picture in DB
			$picture = new Picture([
				'post_id'   => $post->id,
				'filename'  => null,
				'mime_type' => null,
				'position'  => $picturePosition,
			]);
			
			// Upload File
			$destPath = 'files/' . strtolower($post->country_code) . '/' . $post->id;
			$picture->filename = Upload::image($destPath, $file, null, true);
			$picture->mime_type = getUploadedFileMimeType($file);
			
			if (!empty($picture->filename)) {
				$picture->save();
			}
			
			$pictures[] = $picture;
			
			$i++;
		}
		
		return $pictures;
	}
}
