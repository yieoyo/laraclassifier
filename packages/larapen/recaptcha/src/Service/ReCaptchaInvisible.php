<?php

namespace Larapen\ReCaptcha\Service;

class ReCaptchaInvisible extends ReCaptcha
{
	/**
	 * ReCaptchaInvisible constructor.
	 *
	 * @param string $siteKey
	 * @param string $secretKey
	 * @param string $lang
	 */
	public function __construct(string $siteKey, string $secretKey, string $lang)
	{
		parent::__construct($siteKey, $secretKey, $lang, 'invisible');
	}
	
	/**
	 * Write HTML <button> tag in your HTML code
	 * Insert before </form> tag
	 *
	 * @param string|null $buttonInnerHTML
	 *
	 * @return string
	 */
	public function htmlFormButton(?string $buttonInnerHTML = 'Submit'): string
	{
		$btn = '<button class="g-recaptcha" data-sitekey="' . $this->siteKey . '" data-callback="laraReCaptcha">';
		$btn .= !empty($buttonInnerHTML) ? $buttonInnerHTML : 'Submit';
		$btn .= '</button>';
		
		return ($this->version == 'invisible') ? $btn : '';
	}
}
