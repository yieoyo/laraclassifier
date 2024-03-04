<?php

namespace Larapen\Captcha;

use Exception;
use Illuminate\Routing\Controller;

/**
 * @group Captcha
 */
class CaptchaController extends Controller
{
	/**
	 * Get CAPTCHA
	 *
	 * @param Captcha $captcha
	 * @param string $config
	 * @return array|mixed
	 * @throws Exception
	 */
	public function getCaptcha(Captcha $captcha, string $config = 'default')
	{
		if (ob_get_contents()) {
			ob_clean();
		}
		
		return $captcha->create($config);
	}
	
	/**
	 * Get CAPTCHA
	 *
	 * Calling this endpoint is mandatory if the captcha is enabled in the Admin panel.
	 * Return a JSON data with an 'img' item that contains the captcha image to show and a 'key' item that contains the generated key to send for validation.
	 *
	 * @urlParam config string The preconfigured option. Example: flat
	 *
	 * @param Captcha $captcha
	 * @param string $config
	 * @return array|mixed
	 * @throws Exception
	 */
	public function getCaptchaApi(Captcha $captcha, $config = 'default')
	{
		return $captcha->create($config, true);
	}
}
