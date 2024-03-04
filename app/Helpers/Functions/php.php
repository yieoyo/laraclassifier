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

/**
 * Email address prefix (local-part) mask
 *
 * @param string|null $value
 * @param int $escapedChars
 * @return string|null
 */
function email_prefix_mask(?string $value, int $escapedChars = 1): ?string
{
	$atPos = mb_stripos($value, '@');
	if ($atPos === false) {
		return $value;
	}
	
	$emailUsername = mb_substr($value, 0, $atPos);
	$emailDomain = mb_substr($value, ($atPos + 1));
	
	if (!empty($emailUsername) && !empty($emailDomain)) {
		$value = str($emailUsername)->mask('x', $escapedChars) . '@' . $emailDomain;
	}
	
	return $value;
}

/**
 * Get a file extension
 *
 * @param string|null $filename
 * @return string|null
 */
function file_extension(?string $filename): ?string
{
	$tmp = explode('?', getAsString($filename));
	$tmp = explode('.', current($tmp));
	$extension = end($tmp);
	
	return getAsStringOrNull($extension);
}

/**
 * String strip
 *
 * @param string|null $string
 * @return string|null
 */
function str_strip(?string $string): ?string
{
	return trim(preg_replace('/\s\s+/u', ' ', $string));
}

/**
 * String cleaner
 *
 * @param string|null $string
 * @return string|null
 */
function mb_str_cleaner(?string $string): ?string
{
	$string = strip_tags($string, '<br><br/>');
	$string = str_replace(['<br>', '<br/>', '<br />'], "\n", $string);
	$string = preg_replace("/[\r\n]+/", "\n", $string);
	/*
	Remove 4(+)-byte characters from a UTF-8 string
	It seems like MySQL does not support characters with more than 3 bytes in its default UTF-8 charset.
	NOTE: you should not just strip, but replace with replacement character U+FFFD to avoid unicode attacks, mostly XSS:
	http://unicode.org/reports/tr36/#Deletion_of_Noncharacters
	*/
	$string = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $string);
	
	return mb_ucfirst(trim($string));
}

/**
 * String cleaner (Lite version)
 *
 * @param string|null $string
 * @return string|null
 */
function str_cleaner(?string $string): ?string
{
	$string = strip_tags($string);
	$string = html_entity_decode($string);
	$string = strip_tags($string);
	$string = preg_replace('/[\'"]*(<|>)[\'"]*/us', '', $string);
	$string = trim($string);
	
	/*
	Remove non-breaking spaces
	In HTML, the common non-breaking space, which is the same width as the ordinary space character, is encoded as &nbsp; or &#160;.
	In Unicode, it is encoded as U+00A0.
	https://en.wikipedia.org/wiki/Non-breaking_space
	https://graphemica.com/00A0
	*/
	$string = preg_replace('~\x{00a0}~siu', '', $string);
	
	return is_string($string) ? $string : null;
}

/**
 * Tags Cleaner
 * Prevent issues with the #hashtags when they are only numeric
 *
 * @param array|string|null $tagString
 * @param int $limit
 * @param bool $forceArrayReturn
 * @return array|string|null
 */
function taggable(array|string|null $tagString, int $limit = 15, bool $forceArrayReturn = false): array|string|null
{
	if (!is_array($tagString) && !is_string($tagString)) {
		return $forceArrayReturn ? [] : null;
	}
	
	$arrayExpected = false;
	
	if (is_array($tagString)) {
		$tagsArray = $tagString;
		$arrayExpected = true;
	} else {
		$tagsArray = preg_split('|[:,;#_\|\n\t]+|ui', $tagString);
	}
	
	$tags = [];
	$i = 0;
	foreach ($tagsArray as $tag) {
		$tag = str_cleaner($tag);
		
		// Remove all tags (simultaneously) staring and ending by a number
		$tag = preg_replace('/\b\d+\b/ui', '', $tag);
		
		// Remove special characters
		$tag = str_replace([':', ',', ';', '_', '\\', '/', '|', '+'], '', $tag);
		
		// Change the tag case (lowercase)
		$tag = mb_strtolower(trim($tag));
		
		if ($tag != '') {
			if (mb_strlen($tag) > 1) {
				if ($i <= $limit) {
					$tags[] = $tag;
				}
				$i++;
			}
		}
	}
	$tags = array_unique($tags);
	
	if ($arrayExpected || $forceArrayReturn) {
		return $tags;
	}
	
	return !empty($tags) ? implode(',', $tags) : null;
}

/**
 * @return string
 */
function tag_regex_pattern(): string
{
	/*
	 * Tags (Only allow letters, numbers, spaces and ',;_-' symbols)
	 *
	 * Explanation:
	 * [] 	=> character class definition
	 * p{L} => matches any kind of letter character from any language
	 * p{N} => matches any kind of numeric character
	 * _- 	=> matches underscore and hyphen
	 * + 	=> Quantifier — Matches between one to unlimited times (greedy)
	 * /u 	=> Unicode modifier. Pattern strings are treated as UTF-16. Also causes escape sequences to match unicode characters
	 */
	return '/^[\p{L}\p{N} ,;_-]+$/u';
}

/**
 * Remove numeric characters from string
 *
 * @param string|null $string
 * @return string|null
 */
function str_remove_numeric(?string $string): ?string
{
	$string = (is_string($string) && !empty($string)) ? $string : '';
	$string = preg_replace('/\d/u', '', strip_tags($string));
	
	return is_string($string) ? $string : '';
}

/**
 * Check if value is string or numeric
 *
 * @param $value
 * @return bool
 */
function is_stringable($value): bool
{
	return (is_string($value) || is_numeric($value));
}

/**
 * Prevent string containing only digit chars
 *
 * @param string|null $string
 * @return string
 */
function prevent_str_containing_only_digit_chars(?string $string): string
{
	return !does_str_contain_only_digit_chars($string) ? $string : '';
}

/**
 * Check if a given string contains only digit characters
 *
 * @param string|null $string
 * @param bool $withRegex
 * @return bool
 */
function does_str_contain_only_digit_chars(?string $string, bool $withRegex = true): bool
{
	$string = (is_string($string) && !empty($string)) ? $string : '';
	
	$string = str_remove_special_chars($string);
	$string = str_remove_whitespace($string);
	
	if ($withRegex) {
		if (preg_match('/^[0-9]+$/u', $string)) {
			return true;
		}
		
		return false;
	} else {
		for ($i = 0; $i < mb_strlen($string); $i++) {
			if (!is_numeric($string[$i])) {
				return false;
			}
		}
		
		return true;
	}
}

/**
 * Remove all whitespace from a multibyte string
 *
 * @param string|null $string
 * @param string $replacement
 * @return string
 */
function str_remove_whitespace(?string $string, string $replacement = ''): string
{
	$string = (is_string($string) && !empty($string)) ? $string : '';
	
	// White-space = [ \t\r\n\f];
	$pattern = '/\s+/u';
	$string = preg_replace($pattern, $replacement, $string);
	
	return is_string($string) ? $string : '';
}

/**
 * Remove all special characters from a multibyte string
 *
 * $inputString = "Héllo, Wørld! This is a t€st. 123_456! 你好，こんにちは";
 * $cleanedString = str_remove_whitespace($inputString);
 * Output: "Héllo Wørld This is a tst 123456 你好こんにちは"
 *
 * @param string|null $string
 * @param string $replacement
 * @return string
 */
function str_remove_special_chars(?string $string, string $replacement = ''): string
{
	$string = (is_string($string) && !empty($string)) ? $string : '';
	
	// Define a regular expression pattern to match special characters
	// i.e.: Remove non-word characters and whitespaces
	// Word characters are english letters and digits
	// Word characters in multibyte are letters (in all languages) and digits
	// Use the u modifier after the closing delimiter since multibyte characters are allowed
	$pattern = '/[^\p{L}\p{N}\s]/u';
	
	// Perform the multibyte regular expression replace
	$string = preg_replace($pattern, $replacement, $string);
	
	return is_string($string) ? $string : '';
}

/**
 * Remove all non UTF-8 characters
 *
 * Remove Emojis or 4 byte characters.
 * Emojis or BMP character have more than three bytes and maximum of four bytes per character.
 * To store this type of characters, UTF8mb4 character set is needed in MySQL.
 * And it is available only in MySQL 5.5.3 and above versions.
 * Otherwise, remove all 4 byte characters and store it in DB.
 *
 * @param string|null $string
 * @return string
 */
function strip_non_utf(?string $string): string
{
	$string = (is_string($string) && !empty($string)) ? $string : '';
	/*
	 * \p{L} matches any kind of letter from any language
	 * \p{N} matches any kind of numeric character in any script (Optional)
	 * \p{M} matches a character intended to be combined with another character (e.g. accents, umlauts, enclosing boxes, etc.)
	 * [:ascii:] matches a character with ASCII value 0 through 127
	 */
	$string = preg_replace('/[^\p{L}\p{N}\p{M}[:ascii:]]+/ui', '', $string);
	
	return is_string($string) ? $string : '';
}

/**
 * Get URL host
 *
 * @param string|null $url
 * @return string|null
 */
function get_url_host(?string $url): ?string
{
	if (empty($url)) return null;
	
	// in case scheme relative URI is passed, e.g., //www.google.com/
	$url = trim($url, '/');
	
	// If a scheme not included, prepend it
	if (!preg_match('#^http(s)?://#', $url)) {
		$url = 'http' . '://' . $url;
	}
	
	$parts = parse_url($url);
	$host = preg_replace('/^www\./', '', $parts['host']); // remove www
	
	return is_string($host) ? $host : null;
}

/**
 * Add rel="nofollow" to links in string
 *
 * @param string|null $html
 * @param string|null $skip
 * @return string|null
 */
function no_follow_links(?string $html, string $skip = null): ?string
{
	$callback = function ($mach) use ($skip) {
		$link = $mach[1] ?? null;
		$orig = $mach[0] ?? null;
		$isSkipped = (!empty($skip) && str_contains($link, $skip));
		$hasNoFollow = str_contains($link, 'rel=');
		
		return (!$isSkipped && !$hasNoFollow) ? $link . ' rel="nofollow">' : $orig;
	};
	$html = preg_replace_callback("#(<a[^>]+?)>#is", $callback, $html);
	
	return is_string($html) ? $html : null;
}

/**
 * Create auto-links for URLs in string
 *
 * @param string|null $str
 * @param array $attributes
 * @return string|null
 */
function urls_to_links(?string $str, array $attributes = []): ?string
{
	// Transform URL to an HTML link
	$attrs = '';
	foreach ($attributes as $attribute => $value) {
		$attrs .= " {$attribute}=\"{$value}\"";
	}
	
	$str = ' ' . $str;
	
	$pattern = '`([^"=\'>])((http|https|ftp)://[^\s<]+[^\s<\.)])`i';
	$replacement = '$1<a rel="nofollow" href="$2"' . $attrs . ' target="_blank">$2</a>';
	$str = preg_replace($pattern, $replacement, $str);
	
	$str = substr($str, 1);
	
	// Add rel="nofollow" to links
	$httpHost = $_SERVER['HTTP_HOST'] ?? request()->server('HTTP_HOST');
	$parse = parse_url('http' . '://' . $httpHost);
	$str = no_follow_links($str, $parse['host']);
	
	// Find and attach target="_blank" to all href links from text
	return target_blank_links($str);
}

/**
 * Find and attach target="_blank" to all href links in string
 *
 * @param string|null $content
 * @return string|null
 */
function target_blank_links(?string $content): ?string
{
	// Find all links
	preg_match_all('/<a ((?!target)[^>])+?>/ui', $content, $matches);
	
	// Loop only the first array to modify links
	if (is_array($matches) && isset($matches[0])) {
		foreach ($matches[0] as $key => $value) {
			// Take orig link
			$origLink = $value;
			
			// Does it have target="_blank"
			if (!preg_match('/target="_blank"/ui', $origLink)) {
				// Add target = "_blank"
				$newLink = preg_replace("/<a(.*?)>/ui", "<a$1 target=\"_blank\">", $origLink);
				
				// Replace the old link in content with the new link
				$content = str_replace($origLink, $newLink, $content);
			}
		}
	}
	
	return is_string($content) ? $content : null;
}

/**
 * Function to convert hex value to rgb array
 *
 * @param string|null $colour
 * @return array|bool
 *
 * @todo: need to be improved
 */
function hex_to_rgb(?string $colour)
{
	if ($colour[0] == '#') {
		$colour = substr($colour, 1);
	}
	if (strlen($colour) == 6) {
		[$r, $g, $b] = [$colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]];
	} else if (strlen($colour) == 3) {
		[$r, $g, $b] = [$colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]];
	} else {
		return false;
	}
	$r = hexdec($r);
	$g = hexdec($g);
	$b = hexdec($b);
	
	return ['r' => $r, 'g' => $g, 'b' => $b];
}

/**
 * Convert hexdec color string to rgb(a) string
 *
 * @param $color
 * @param bool $opacity
 * @return string
 *
 * @todo: need to be improved
 */
function hex_to_rgba($color, bool $opacity = false): string
{
	$default = 'rgb(0,0,0)';
	
	//Return default if no color provided
	if (empty($color)) {
		return $default;
	}
	
	//Sanitize $color if "#" is provided
	if ($color[0] == '#') {
		$color = substr($color, 1);
	}
	
	//Check if color has 6 or 3 characters and get values
	if (strlen($color) == 6) {
		$hex = [$color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]];
	} else if (strlen($color) == 3) {
		$hex = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
	} else {
		return $default;
	}
	
	//Convert hexadec to rgb
	$rgb = array_map('hexdec', $hex);
	
	//Check if opacity is set(rgba or rgb)
	if ($opacity) {
		if (abs($opacity) > 1) {
			$opacity = 1.0;
		}
		$output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
	} else {
		$output = 'rgb(' . implode(",", $rgb) . ')';
	}
	
	// Return rgb(a) color string
	return $output;
}

/**
 * ucfirst() function for multibyte character encodings
 *
 * @param string|null $string
 * @param string $encoding
 * @return string|null
 */
function mb_ucfirst(?string $string, string $encoding = 'utf-8'): ?string
{
	if (empty($string)) {
		return null;
	}
	
	$strLen = mb_strlen($string, $encoding);
	$firstChar = mb_substr($string, 0, 1, $encoding);
	$then = mb_substr($string, 1, $strLen - 1, $encoding);
	
	return mb_strtoupper($firstChar, $encoding) . $then;
}

/**
 * ucwords() function for multibyte character encodings
 *
 * @param string|null $string
 * @param string $encoding
 * @return string|null
 */
function mb_ucwords(?string $string, string $encoding = 'utf-8'): ?string
{
	if (empty($string)) {
		return null;
	}
	
	$tab = [];
	
	// Split the phrase by any number of space characters, which include " ", \r, \t, \n and \f
	$words = preg_split('/\s+/ui', $string);
	if (!empty($words)) {
		foreach ($words as $key => $word) {
			$tab[$key] = mb_ucfirst($word, $encoding);
		}
	}
	
	return (!empty($tab)) ? implode(' ', $tab) : null;
}

/**
 * parse_url() function for multi-bytes character encodings
 *
 * @param string|null $url
 * @param int $component
 * @return mixed
 */
function mb_parse_url(?string $url, int $component = -1)
{
	$callback = function ($matches) {
		return urlencode($matches[0]);
	};
	$encodedUrl = preg_replace_callback('%[^:/@?&=#]+%usD', $callback, $url);
	
	if (empty($encodedUrl)) {
		return null;
	}
	
	$parts = parse_url($encodedUrl, $component);
	
	if ($parts === false) {
		throw new \InvalidArgumentException('Malformed URL: ' . $url);
	}
	
	if (is_array($parts) && count($parts) > 0) {
		foreach ($parts as $name => $value) {
			$parts[$name] = urldecode($value);
		}
	}
	
	return $parts;
}

/**
 * Friendly UTF-8 URL for all languages
 *
 * @param string|null $string
 * @param string $separator
 * @return string|null
 */
function slugify(?string $string, string $separator = '-'): ?string
{
	// Remove accents using WordPress API method.
	$string = remove_accents($string);
	
	// Slug
	$string = mb_strtolower($string);
	$string = @trim($string);
	$replace = "/(\\s|\\" . $separator . ")+/mu";
	$subst = $separator;
	$string = preg_replace($replace, $subst, $string);
	
	// Remove unwanted punctuation, convert some to '-'
	$puncTable = [
		// remove
		"'"  => '',
		'"'  => '',
		'`'  => '',
		'='  => '',
		'+'  => '',
		'*'  => '',
		'&'  => '',
		'^'  => '',
		''   => '',
		'%'  => '',
		'$'  => '',
		'#'  => '',
		'@'  => '',
		'!'  => '',
		'<'  => '',
		'>'  => '',
		'?'  => '',
		// convert to minus
		'['  => '-',
		']'  => '-',
		'{'  => '-',
		'}'  => '-',
		'('  => '-',
		')'  => '-',
		' '  => '-',
		','  => '-',
		';'  => '-',
		':'  => '-',
		'/'  => '-',
		'|'  => '-',
		'\\' => '-',
	];
	$string = str_replace(array_keys($puncTable), array_values($puncTable), $string);
	
	// Clean up multiple '-' characters
	$string = preg_replace('/-{2,}/', '-', $string);
	
	// Remove trailing '-' character if string not just '-'
	if ($string != '-') {
		$string = rtrim($string, '-');
	}
	
	if ($separator != '-') {
		$string = str_replace('-', $separator, $string);
	}
	
	return is_string($string) ? $string : null;
}

/**
 * Get file/folder permissions
 *
 * @param string $path
 * @return int
 */
function get_perms(string $path): int
{
	$permissions = fileperms($path);
	$readablePermissions = substr(sprintf('%o', $permissions), -4);
	
	return intval($readablePermissions);
}

/**
 * Get number plural (0 and 1 for singular, >=2 for plural)
 * Required for russian pluralization
 *
 * @param int|float|null $number
 * @param bool|null $isRussianLangPluralization
 * @return int|float|null
 */
function number_plural(int|float|null $number, ?bool $isRussianLangPluralization = false): int|float|null
{
	if (!is_numeric($number)) {
		$number = (int)$number;
	}
	
	if ($isRussianLangPluralization === true) {
		// Russian pluralization rules
		$typeOfPlural = (($number % 10 == 1) && ($number % 100 != 11))
			? 0
			: ((($number % 10 >= 2)
				&& ($number % 10 <= 4)
				&& (($number % 100 < 10)
					|| ($number % 100 >= 20)))
				? 1
				: 2
			);
	} else {
		// No rule for other languages
		$typeOfPlural = $number;
	}
	
	return $typeOfPlural;
}

/**
 * Make sure that setting array contains only string, numeric or null elements
 *
 * @param $value
 * @return array|null
 */
function settingArrayElements($value): ?array
{
	if (!is_array($value)) {
		return null;
	}
	
	if (!empty($value)) {
		$array = [];
		foreach ($value as $subColumn => $subValue) {
			$array[$subColumn] = (is_string($subValue) || is_numeric($subValue)) ? $subValue : null;
		}
		$value = $array;
	}
	
	return $value;
}

/**
 * Check if the string is a (valid) date
 *
 * @param string|null $value
 * @return bool
 */
function isValidDate(?string $value): bool
{
	if (strtotime($value) !== false) {
		$value = date('Y-m-d H:i', strtotime($value));
	}
	
	$value = str_replace('/', '-', $value);
	$value = str_replace('.', '-', $value);
	
	$isValid = (\DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false);
	if (!$isValid) {
		$isValid = (\DateTime::createFromFormat('Y-m-d H:i', $value) !== false);
	}
	if (!$isValid) {
		$isValid = (\DateTime::createFromFormat('Y-m-d', $value) !== false);
	}
	
	return $isValid;
}

/**
 * Check if exec() function is available
 *
 * @return boolean
 */
function phpExecFuncEnabled(): bool
{
	try {
		// make a small test
		exec("ls");
		
		return function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));
	} catch (\Throwable $e) {
		return false;
	}
}

/**
 * Check if function is enabled
 *
 * @param string $name
 * @return bool
 */
function phpFuncEnabled(string $name): bool
{
	try {
		$disabled = array_map('trim', explode(',', ini_get('disable_functions')));
		
		return !in_array($name, $disabled);
	} catch (\Throwable $e) {
		return false;
	}
}

/**
 * Check if the PHP Exif component is enabled
 *
 * @return bool
 */
function exifExtIsEnabled(): bool
{
	try {
		if (extension_loaded('exif') && function_exists('exif_read_data')) {
			return true;
		}
		
		return false;
	} catch (\Throwable $e) {
		return false;
	}
}

/**
 * Build HTML attributes with PHP array
 *
 * @param array|null $attributes
 * @return string
 */
function buildAttributes(?array $attributes): string
{
	if (empty($attributes)) {
		return '';
	}
	
	$attributePairs = [];
	foreach ($attributes as $key => $val) {
		if (is_int($key)) {
			$attributePairs[] = $val;
		} else {
			$val = htmlspecialchars($val, ENT_QUOTES);
			$attributePairs[] = "{$key}=\"{$val}\"";
		}
	}
	
	$out = trim(implode(' ', $attributePairs));
	
	if (!empty($out)) {
		$out = ' ' . $out;
	}
	
	return $out;
}

/**
 * Get an image extension from base64 string
 *
 * @param string|null $bufferImg
 * @param bool $recursive
 * @return bool
 */
function is_png(?string $bufferImg, bool $recursive = true): bool
{
	$f = finfo_open();
	$result = finfo_buffer($f, $bufferImg, FILEINFO_MIME_TYPE);
	
	if (!str_contains($result, 'image') && $recursive) {
		// Plain Text
		return str_contains($bufferImg, 'image/png');
	}
	
	return ($result == 'image/png');
}

/**
 * Get Phone's National Format
 *
 * Example: BE: 012/34.56.78 => 012 34 56 78
 *
 * @param string|null $phone
 * @return string
 */
function normalizePhoneNumber(?string $phone): string
{
	$phone = (!is_null($phone)) ? $phone : '';
	$phone = preg_replace('/\D+/', '', $phone);
	
	return is_string($phone) ? $phone : '';
}

/**
 * @param string|null $phone
 * @param string|null $provider
 * @return string|null
 */
function setPhoneSign(?string $phone, ?string $provider = null): ?string
{
	if ($provider == 'vonage') {
		// Vonage doesn't support the sign '+'
		if (str_starts_with($phone, '+')) {
			$phone = '' . str_replace('+', '', $phone);
		}
	}
	
	if ($provider == 'twilio') {
		// Twilio requires the sign '+'
		if (!str_starts_with($phone, '+')) {
			$phone = '+' . $phone;
		}
	}
	
	if (!in_array($provider, ['vonage', 'twilio'])) {
		if (!str_starts_with($phone, '+')) {
			$phone = '+' . $phone;
		}
	}
	
	return ($phone == '+') ? '' : $phone;
}

/**
 * Remove all unmatched variables patterns (e.g. {foo}) from a string
 *
 * @param string|null $string
 * @return string
 */
function removeUnmatchedPatterns(?string $string): string
{
	$string = preg_replace('|\{[^}]+}|ui', '', $string);
	$string = preg_replace('|,(\s*,)+|ui', ',', $string);
	$string = preg_replace('|\s\s+|ui', ' ', $string);
	
	return trim($string, " \n\r\t\v\0,-");
}

/**
 * Check if an array contains only empty items/elements
 *
 * @param array|null $array
 * @return bool
 * @todo: Make it recursive
 */
function arrayItemsAreEmpty(?array $array): bool
{
	if (empty($array)) {
		return true;
	}
	
	// Check if the array contains a non-empty element
	$newArray = $array;
	foreach ($array as $key => $value) {
		if (empty($value) && array_key_exists($key, $newArray)) {
			unset($newArray[$key]);
		}
	}
	if (!empty($newArray)) {
		return false;
	} else {
		return true;
	}
}

/**
 * Redirect (Prevent Browser Cache)
 *
 * @param string $url
 * @param int $status (301 => Moved Permanently | 302 => Moved Temporarily)
 * @param array $headers
 */
function redirectUrl(string $url, int $status = 301, array $headers = [])
{
	// Headers have been sent
	// Any more header lines can not be added using the header() function once the header block has already been sent.
	if (headers_sent()) {
		redirectUrlWithHtml($url);
		exit();
	}
	
	// Apply headers (by adding new header lines)
	if (is_array($headers) && !empty($headers)) {
		foreach ($headers as $key => $value) {
			if (str_contains($value, 'post-check') || str_contains($value, 'pre-check')) {
				header($key . ": " . $value, false);
			} else {
				header($key . ": " . $value);
			}
		}
	}
	
	// Redirect
	header("Location: " . $url, true, $status);
	exit();
}

/**
 * Redirect URL (with GET method) in HTML
 * Note: Don't prevent browser cache
 *
 * @param string $url
 * @return void
 */
function redirectUrlWithHtml(string $url)
{
	$out = '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Redirection...</title>
        <script type="text/javascript">
            window.location.href = "' . $url . '"
        </script>
        <noscript>
        	<meta http-equiv="refresh" content="0; url=' . $url . '">
        </noscript>
    </head>
    <body>
        If you are not redirected automatically, follow this <a href="' . $url . '">link</a>.
    </body>
</html>';
	
	echo $out;
	exit();
}

/**
 * Split a name into the first name and last name
 *
 * @param string|null $input
 * @return array
 */
function splitName(?string $input): array
{
	$output = [];
	
	$space = mb_strpos($input, ' ');
	if ($space !== false) {
		$output['firstName'] = mb_substr($input, 0, $space);
		$output['lastName'] = mb_substr($input, $space, strlen($input));
	} else {
		$output['firstName'] = '';
		$output['lastName'] = $input;
	}
	
	return $output;
}

/**
 * Zero leading for numeric values
 *
 * @param string|null $value
 * @param int $padLength
 * @return string|null
 */
function zeroLead(?string $value, int $padLength = 2): ?string
{
	if (is_numeric($value)) {
		$value = str_pad($value, $padLength, '0', STR_PAD_LEFT);
	}
	
	return $value;
}

/**
 * Extract only digit characters
 *
 * @param string|null $value
 * @param int|null $default
 * @return string|int|null
 */
function strToDigit(?string $value, int $default = null)
{
	$value = trim(preg_replace('/[^0-9]/', '', $value));
	if (empty($value)) {
		$value = $default;
	}
	
	return $value;
}

/**
 * Extract only digit characters and Convert the result in integer
 *
 * @param string|null $value
 * @param int $default
 * @return int
 */
function strToInt(?string $value, int $default = 0): int
{
	return (int)strToDigit($value, $default);
}

/**
 * Change whitespace (\n and \r) to simple space in string
 * PHP_EOL catches newlines that \n, \r\n, \r miss.
 *
 * @param string|null $string
 * @return array|string|string[]
 */
function changeWhiteSpace(?string $string)
{
	return str_replace(PHP_EOL, ' ', $string);
}

/**
 * PHP round() function that always return a float value in any language
 *
 * @param float|int $val
 * @param int $precision
 * @param int $mode
 * @return string
 */
function round_val($val, int $precision = 0, int $mode = PHP_ROUND_HALF_UP): string
{
	return number_format((float)round($val, $precision, $mode), $precision, '.', '');
}

/**
 * Print JavaScript code in HTML
 *
 * @param string|null $code
 * @return array|string|string[]
 */
function printJs(?string $code)
{
	// Get the External JS, and make for them a pattern
	$exRegex = '/<script([a-z0-9\-_ ]+)src=([^>]+)>(.*?)<\/script>/ius';
	$replace = '<#EXTERNALJS#$1src=$2>$3</#EXTERNALJS#>';
	$code = preg_replace($exRegex, $replace, $code);
	
	// Get the Inline JS, and make for them a pattern
	$inRegex = '/<script([^>]*)>(.*?)<\/script>/ius';
	$replace = '<#INLINEJS#$1>$2</#INLINEJS#>';
	while (preg_match($inRegex, $code)) {
		$code = preg_replace($inRegex, $replace, $code);
	}
	
	// Replace the patterns
	$code = str_replace(['#EXTERNALJS#', '#INLINEJS#'], 'script', $code);
	
	// The code doesn't contain a <script> tag
	if (!preg_match($inRegex, $code)) {
		$code = '<script type="text/javascript">' . "\n" . $code . "\n" . '</script>';
	}
	
	return $code;
}

/**
 * Print CSS codes in HTML
 *
 * @param string|null $code
 * @return string
 */
function printCss(?string $code): string
{
	$code = preg_replace('/<[^>]+>/i', '', $code);
	
	return '<style>' . "\n" . $code . "\n" . '</style>';
}

/**
 * Count the total number of line of a given file without loading the entire file.
 * This is effective for large file
 *
 * @param string $path
 * @return int
 */
function lineCount(string $path): int
{
	$file = new \SplFileObject($path, 'r');
	$file->seek(PHP_INT_MAX);
	
	return $file->key() + 1;
}

/**
 * Escape characters with slashes like in C & Remove the double white spaces
 *
 * @param string|null $string
 * @param string $quote
 * @return null|string|string[]
 */
function addcslashesLite(?string $string, string $quote = '"')
{
	return preg_replace("/\s+/ui", " ", addcslashes($string, $quote));
}

/**
 * Add http:// if it doesn't exist in the URL
 * Recognizes ftp://, ftps://, http:// and https:// in a case-insensitive way.
 *
 * @param string|null $url
 * @return string|null
 */
function addHttp(?string $url): ?string
{
	if (!empty($url)) {
		if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
			$url = 'http' . '://' . $url;
		}
	}
	
	return $url;
}

/**
 * Determine if php is running at the command line
 *
 * @return bool
 */
function isCli(): bool
{
	if (defined('STDIN')) {
		return true;
	}
	
	if (php_sapi_name() === 'cli') {
		return true;
	}
	
	if (array_key_exists('SHELL', $_ENV)) {
		return true;
	}
	
	if (empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
		return true;
	}
	
	if (!array_key_exists('REQUEST_METHOD', $_SERVER)) {
		return true;
	}
	
	return false;
}

/**
 * Convert UTF-8 HTML to ANSI
 *
 * https://stackoverflow.com/a/7061511
 * https://onlinehelp.coveo.com/en/ces/7.0/administrator/what_is_the_difference_between_ansi_and_utf-8_uri_formats.htm
 * https://stackoverflow.com/questions/701882/what-is-ansi-format
 *
 * @param string|null $string
 * @return string|null
 */
function convertUTF8HtmlToAnsi(?string $string): ?string
{
	/*
	 * 1. Escaped Unicode characters to HTML hex references. E.g. \u00e9 => &#x00e9;
	 * 2. Convert HTML entities to their corresponding characters. E.g. &#x00e9; => é
	 */
	$string = preg_replace('/\\\\u([a-fA-F0-9]{4})/ui', '&#x\\1;', $string);
	
	return html_entity_decode($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
}

/**
 * @param string|null $string
 * @return string|null
 */
function nlToBr(?string $string): ?string
{
	// Replace multiple (one or more) line breaks with a single one.
	$string = preg_replace("/[\r\n]+/", "\n", $string);
	
	return nl2br($string);
}

/**
 * Convert only the translations array to json in an array
 *
 * @param array|null $entry
 * @param bool $unescapedUnicode
 * @return array|null
 */
function arrayTranslationsToJson(?array $entry, bool $unescapedUnicode = true): ?array
{
	if (empty($entry)) {
		return $entry;
	}
	
	$neyEntry = [];
	foreach ($entry as $key => $value) {
		if (is_array($value)) {
			$neyEntry[$key] = ($unescapedUnicode) ? json_encode($value, JSON_UNESCAPED_UNICODE) : json_encode($value);
		} else {
			$neyEntry[$key] = $value;
		}
	}
	
	return $neyEntry;
}

/**
 * @param int|null $decimalPlaces
 * @return string
 */
function getInputNumberStep(int $decimalPlaces = null): string
{
	if (empty($decimalPlaces) || $decimalPlaces <= 0) {
		$decimalPlaces = 2;
	}
	
	return '0.' . (str_pad('1', $decimalPlaces, '0', STR_PAD_LEFT));
}

/**
 * Create Random String
 *
 * @param int $length
 * @return string
 */
function createRandomString(int $length = 6): string
{
	$str = '';
	$chars = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
	$max = count($chars) - 1;
	for ($i = 0; $i < $length; $i++) {
		$rand = mt_rand(0, $max);
		$str .= $chars[$rand];
	}
	
	return $str;
}

/**
 * Increases or decreases the brightness of a color by a percentage of the current brightness.
 *
 * Supported formats: '#FFF', '#FFFFFF', 'FFF', 'FFFFFF'
 * A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
 *
 * @param string|null $hexCode
 * @param float $percent
 * @return string
 */
function colourBrightness(?string $hexCode, float $percent): string
{
	$hexCode = ltrim($hexCode, '#');
	
	if (strlen($hexCode) == 3) {
		$hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
	}
	
	$hexCode = array_map('hexdec', str_split($hexCode, 2));
	
	foreach ($hexCode as & $color) {
		$adjustableLimit = $percent < 0 ? $color : 255 - $color;
		$adjustAmount = ceil($adjustableLimit * $percent);
		
		$color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
	}
	
	return '#' . implode($hexCode);
}

/**
 * Luminosity Contrast algorithm
 * Given a background color, black or white text
 *
 * Will return '#FFFFFF'
 * echo getContrastColor('#FF0000');
 *
 * @param string|null $hexColor
 * @return string
 */
function getContrastColor(?string $hexColor): string
{
	// hexColor RGB
	$r1 = hexdec(substr($hexColor, 1, 2));
	$g1 = hexdec(substr($hexColor, 3, 2));
	$b1 = hexdec(substr($hexColor, 5, 2));
	
	// Black RGB
	$blackColor = '#000000';
	$rToBlackColor = hexdec(substr($blackColor, 1, 2));
	$gToBlackColor = hexdec(substr($blackColor, 3, 2));
	$bToBlackColor = hexdec(substr($blackColor, 5, 2));
	
	// Calc contrast ratio
	$l1 = 0.2126 * pow($r1 / 255, 2.2)
		+ 0.7152 * pow($g1 / 255, 2.2)
		+ 0.0722 * pow($b1 / 255, 2.2);
	
	$l2 = 0.2126 * pow($rToBlackColor / 255, 2.2)
		+ 0.7152 * pow($gToBlackColor / 255, 2.2)
		+ 0.0722 * pow($bToBlackColor / 255, 2.2);
	
	$contrastRatio = 0;
	if ($l1 > $l2) {
		$contrastRatio = (int)(($l1 + 0.05) / ($l2 + 0.05));
	} else {
		$contrastRatio = (int)(($l2 + 0.05) / ($l1 + 0.05));
	}
	
	// If contrast is more than 5, return black color
	if ($contrastRatio > 5) {
		return '#000000';
	} else {
		// If not, return white color.
		return '#FFFFFF';
	}
}

/**
 * CSS Minify
 * Note: This works only for CSS code
 *
 * @param string|null $code
 * @return string
 */
function cssMinify(?string $code): string
{
	// Make it into one long line
	$code = str_replace(["\n", "\r"], '', $code);
	
	// Replace all multiple spaces by one space
	$code = preg_replace('!\s+!', ' ', $code);
	
	// Replace some unneeded spaces, modify as needed
	$code = str_replace([' {', ' }', '{ ', '; '], ['{', '}', '{', ';'], $code);
	
	// Remove comments
	$code = str_replace('/*', '_COMMENT_START', $code);
	$code = str_replace('*/', 'COMMENT_END_', $code);
	$code = preg_replace('/_COMMENT_START.*?COMMENT_END_/s', '', $code);
	
	return trim($code);
}

/**
 * Get a /.env file key's value
 *
 * @param $key
 * @return string|null
 */
function get_dotenv_value($key): ?string
{
	if (empty($key)) return null;
	
	$value = null;
	
	$filePath = realpath(__DIR__ . '/../../../.env');
	if (file_exists($filePath)) {
		$content = file_get_contents($filePath);
		$matches = [];
		preg_match('/' . $key . '=(.*)[^\n]*/', $content, $matches);
		$value = $matches[1] ?? null;
		$value = is_string($value) ? trim($value) : null;
	}
	
	return $value;
}

/**
 * Get files list including those in subdirectories using glob()
 * Note: Does not support flag GLOB_BRACE
 *
 * @param string $pattern
 * @param int $flags
 * @return bool|array
 */
function glob_recursive(string $pattern, int $flags = 0): bool|array
{
	$files = glob($pattern, $flags);
	foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
		$files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
	}
	
	return $files;
}

/**
 * Remove a directory recursively
 *
 * @param string $dir
 * @return bool
 */
function remove_directory(string $dir): bool
{
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != '.' && $object != '..') {
				if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . '/' . $object)) {
					remove_directory($dir . DIRECTORY_SEPARATOR . $object);
				} else {
					unlink($dir . DIRECTORY_SEPARATOR . $object);
				}
			}
		}
		
		return rmdir($dir);
	}
	
	return false;
}

/**
 * Check if a directory exists and is not a symlink
 * Note: The is_dir() function returns true for symlink
 *
 * @param string $dirPath
 * @return bool
 */
function is_real_dir(string $dirPath): bool
{
	return is_dir($dirPath) && !is_link($dirPath);
}

/**
 * Zip a directory and its contents
 *
 * @param $sourceDir
 * @param $zipFile
 * @return bool
 */
function zip_directory($sourceDir, $zipFile): bool
{
	if (!(extension_loaded('zip') && class_exists('\ZipArchive'))) {
		return false;
	}
	
	if (!file_exists($sourceDir)) {
		return false;
	}
	
	try {
		// Check if the destination directory exists, if not, create it
		// Get the zip file directory
		$destinationDir = dirname($zipFile);
		if (!is_dir($destinationDir)) {
			mkdir($destinationDir, 0777, true);
		}
		
		// Zip the file
		$zip = new \ZipArchive();
		
		if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
			return false;
		}
		
		$sourceDir = realpath($sourceDir);
		
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($sourceDir),
			RecursiveIteratorIterator::SELF_FIRST
		);
		
		foreach ($files as $file) {
			$file = realpath($file);
			
			if (is_dir($file)) {
				$zip->addEmptyDir(str_replace($sourceDir . '/', '', $file . '/'));
			} else if (is_file($file)) {
				$zip->addFile($file, str_replace($sourceDir . '/', '', $file));
			}
		}
		
		$zip->close();
		
		return file_exists($zipFile);
	} catch (\Throwable $e) {
	}
	
	return false;
}

/**
 * Extract a zip file
 *
 * @param $zipFile
 * @param $extractTo
 * @return bool
 */
function extract_zip($zipFile, $extractTo): bool
{
	if (!(extension_loaded('zip') && class_exists('\ZipArchive'))) {
		return false;
	}
	
	if (!file_exists($zipFile)) {
		return false;
	}
	
	try {
		$zip = new \ZipArchive();
		$zip->open($zipFile);
		$zip->extractTo($extractTo);
		$zip->close();
		
		return true;
	} catch (\Throwable $e) {
	}
	
	return false;
}

/**
 * Escape <code></code> tag content
 *
 * @param $html
 * @return string|null
 */
function escapeCodeTagContent($html): ?string
{
	if (!is_string($html)) return null;
	
	preg_match_all('/<code>(.+?)<\/code>/u', $html, $matches);
	$array = $matches[1] ?? [];
	if (!empty($array)) {
		foreach ($array as $codeStr) {
			$codeStrEnc = $codeStr;
			
			$codeStrEnc = preg_replace('/<([^>]*)>/u', '&lt;$1&gt;', $codeStrEnc);
			$codeStrEnc = str_replace('&amp;', '&', $codeStrEnc);
			
			$search = '<code>' . $codeStr . '</code>';
			$replace = '<code>' . $codeStrEnc . '</code>';
			$html = str_replace($search, $replace, $html);
		}
	}
	
	return is_string($html) ? $html : null;
}

/**
 * @param $value
 * @return string|null
 */
function getAsStringOrNull($value): ?string
{
	return is_string($value) ? $value : null;
}

/**
 * @param $value
 * @param string|null $default
 * @return string
 */
function getAsString($value, ?string $default = ''): string
{
	if (is_null($default)) {
		$default = '';
	}
	
	return is_string($value) ? $value : $default;
}
