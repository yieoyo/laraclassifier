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

namespace App\Helpers;

class VideoEmbedding
{
	/**
	 * @param $link
	 * @return array|null
	 * @throws \Exception
	 */
	public static function extractVideoId($link): ?array
	{
		$platform = self::getVideoPlatform($link);
		if (empty($platform)) {
			return null;
		}
		
		$method = __FUNCTION__ . ucfirst($platform);
		$class = __CLASS__;
		if (!method_exists($class, $method)) {
			// $message = 'The method ' . $method . ' does not exist in ' . $class;
			// throw new \Exception($message);
			return null;
		}
		
		$videoId = self::$method($link);
		if (empty($videoId)) {
			return null;
		}
		
		return ['platform' => $platform, 'videoId' => $videoId];
	}
	
	/**
	 * @param $link
	 * @return string|null
	 * @throws \Exception
	 */
	public static function getVideoUrl($link): ?string
	{
		$extracted = self::extractVideoId($link);
		if (empty($extracted)) {
			return null;
		}
		
		$method = __FUNCTION__ . ucfirst($extracted['platform']);
		$class = __CLASS__;
		if (!method_exists($class, $method)) {
			// $message = 'The method ' . $method . ' does not exist in ' . $class;
			// throw new \Exception($message);
			return null;
		}
		
		return self::$method($extracted['videoId']);
	}
	
	/**
	 * @param $link
	 * @param int $width
	 * @param int $height
	 * @return string|null
	 * @throws \Exception
	 */
	public static function embedVideo($link, int $width = 640, int $height = 360): ?string
	{
		$extracted = self::extractVideoId($link);
		if (empty($extracted)) {
			return null;
		}
		
		$method = __FUNCTION__ . ucfirst($extracted['platform']);
		$class = __CLASS__;
		if (!method_exists($class, $method)) {
			// $message = 'The method ' . $method . ' does not exist in ' . $class;
			// throw new \Exception($message);
			return null;
		}
		
		return self::$method($extracted['videoId'], $width, $height);
	}
	
	/**
	 * Get the video platform name
	 *
	 * @param $link
	 * @return string|null
	 */
	public static function getVideoPlatform($link): ?string
	{
		if (empty($link)) {
			return null;
		}
		
		if (str_contains($link, 'youtu')) {
			return 'youtube';
		} else if (str_contains($link, 'vimeo')) {
			return 'vimeo';
		} else {
			return null;
		}
	}
	
	// ====================================
	
	/**
	 * Extract YouTube video ID from a link
	 *
	 * @param $link
	 * @return string|null
	 */
	/*
	 * (?:				 				# Group to match embed codes
	 *		(?:<iframe [^>]*src=")?	 	# If iframe match up to first quote of src
	 *		|(?:				 		# Group to match if older embed
	 *			(?:<object .*>)?		# Match opening Object tag
	 *			(?:<param .*</param>)*  # Match all param tags
	 *			(?:<embed [^>]*src=")?  # Match embed tag to the first quote of src
	 *		)?				 			# End older embed code group
	 *	)?				 				# End embed code groups
	 *	(?:				 				# Group youtube url
	 *		https?:\/\/		         	# Either http or https
	 *		(?:[\w]+\.)*		        # Optional subdomains
	 *		(?:               	        # Group host alternatives.
	 *		youtu\.be/      	        # Either youtu.be,
	 *		| youtube\.com		 		# or youtube.com
	 *		| youtube-nocookie\.com	 	# or youtube-nocookie.com
	 *		)				 			# End Host Group
	 *		(?:\S*[^\w\-\s])?       	# Extra stuff up to VIDEO_ID
	 *		([\w\-]{11})		        # $1: VIDEO_ID is numeric
	 *		[^\s]*			 			# Not a space
	 *	)				 				# End group
	 *	"?				 				# Match end quote if part of src
	 *	(?:[^>]*>)?			 			# Match any extra stuff up to close brace
	 *	(?:				 				# Group to match last embed code
	 *		</iframe>		         	# Match the end of the iframe
	 *		|</embed></object>	        # or Match the end of the older embed
	 *	)?				 				# End Group of last bit of embed code
	 */
	public static function extractVideoIdYoutube($link): ?string
	{
		if (empty($link)) {
			return null;
		}
		
		// Match YouTube link and embed code
		$pattern = '~
			(?:
				(?:<iframe [^>]*src=")?
				|(?:
					(?:<object .*>)?
					(?:<param .*</param>)*
					(?:<embed [^>]*src=")?
				)?
			)?
			(?:
				https?:\/\/
				(?:[\w]+\.)*
				(?:
				youtu\.be/
				| youtube\.com
				| youtube-nocookie\.com
				)
				(?:\S*[^\w\-\s])?
				([\w\-]{11})
				[^\s]*
			)
			"?
			(?:[^>]*>)?
			(?:
				</iframe>
				|</embed></object>
			)?
			~ix';
		
		preg_match($pattern, $link, $matches);
		$id = (!empty($matches[1])) ? $matches[1] : null;
		
		return is_stringable($id) ? $id : null;
	}
	
	/**
	 * Extract Vimeo video ID from a link
	 *
	 * @param $link
	 * @return string|null
	 */
	/*
	 * (?:<iframe [^>]*src=")?		# If iframe match up to first quote of src
	 *	(?:							# Group vimeo url
	 *		https?:\/\/				# Either http or https
	 *		(?:[\w]+\.)*			# Optional subdomains
	 *		vimeo\.com				# Match vimeo.com
	 *		(?:[\/\w]*\/videos?)?	# Optional video sub directory this handles groups links also
	 *		\/						# Slash before Id
	 *		([0-9]+)				# $1: VIDEO_ID is numeric
	 *		[^\s]*					# Not a space
	 *	)							# End group
	 *	"?							# Match end quote if part of src
	 *	(?:[^>]*></iframe>)?		# Match the end of the iframe
	 *	(?:<p>.*</p>)?		        # Match any title information stuff
	 */
	public static function extractVideoIdVimeo($link): ?string
	{
		if (empty($link)) {
			return null;
		}
		
		// Match Vimeo link and embed code
		$pattern = '~
			(?:<iframe [^>]*src=")?
			(?:
				https?:\/\/
				(?:[\w]+\.)*
				vimeo\.com
				(?:[\/\w]*\/videos?)?
				\/
				([0-9]+)
				[^\s]*
			)
			"?
			(?:[^>]*></iframe>)?
			(?:<p>.*</p>)?
			~ix';
		
		preg_match($pattern, $link, $matches);
		$id = (!empty($matches[1])) ? $matches[1] : null;
		
		return is_stringable($id) ? $id : null;
	}
	
	public static function getVideoUrlYoutube($videoId): ?string
	{
		$url = null;
		
		if (!empty($videoId)) {
			$url = 'https://www.youtube.com/watch?v=' . $videoId;
		}
		
		return $url;
	}
	
	public static function getVideoUrlVimeo($videoId): ?string
	{
		$url = null;
		
		if (!empty($videoId)) {
			$url = 'https://vimeo.com/' . $videoId;
		}
		
		return $url;
	}
	
	public static function embedVideoYoutube($videoId, $width = 560, $height = 315): ?string
	{
		$code = null;
		
		if (!empty($videoId)) {
			$width = ((int)$width > 10) ? (int)$width : 560;
			$height = ((int)$height > 10) ? (int)$height : 315;
			
			$code = '<iframe width="' . $width . '" height="' . $height . '"
	src="https://www.youtube.com/embed/' . $videoId . '"
	class="embed-responsive-item"
	frameborder="0"
	allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
		}
		
		return $code;
	}
	
	public static function embedVideoVimeo($videoId, $width = 640, $height = 360): ?string
	{
		$code = null;
		
		if (!empty($videoId)) {
			$width = ((int)$width > 10) ? (int)$width : 640;
			$height = ((int)$height > 10) ? (int)$height : 360;
			
			$code = '<iframe width="' . $width . '" height="' . $height . '"
	src="https://player.vimeo.com/video/' . $videoId . '"
	class="embed-responsive-item"
	frameborder="0"
	allow="autoplay; fullscreen" allowfullscreen></iframe>';
		}
		
		return $code;
	}
	
	/**
	 * Extract all YouTube videos IDs
	 *
	 * @param $url
	 * @return array
	 */
	public static function extractAllYoutubeVideoIds($url): array
	{
		// Get all YouTube videos IDs
		preg_match_all('#(?<=v=|v/|vi=|vi/|embed/|youtu.be/)[a-zA-Z0-9_-]{11}#', $url, $matches);
		$ids = (!empty($matches[0])) ? $matches[0] : [];
		
		return is_array($ids) ? $ids : [];
	}
}
