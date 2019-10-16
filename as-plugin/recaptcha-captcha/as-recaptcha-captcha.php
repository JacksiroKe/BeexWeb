<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	File: as-plugin/recaptcha-captcha/as-recaptcha-captcha.php
	Description: Captcha module for reCAPTCHA


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.appsmata.org/license.php
*/

class as_recaptcha_captcha
{
	private $directory;
	private $errorCodeMessages;

	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;

		// human-readable error messages (though these are not currently displayed anywhere)
		$this->errorCodeMessages = array(
			'missing-input-secret' => 'The secret parameter is missing.',
			'invalid-input-secret' => 'The secret parameter is invalid or malformed.',
			'missing-input-response' => 'The response parameter is missing.',
			'invalid-input-response' => 'The response parameter is invalid or malformed.',
		);
	}

	public function admin_form()
	{
		$saved = false;

		if (as_clicked('recaptcha_save_button')) {
			as_opt('recaptcha_public_key', as_post_text('recaptcha_public_key_field'));
			as_opt('recaptcha_private_key', as_post_text('recaptcha_private_key_field'));

			$saved = true;
		}

		$pub = trim(as_opt('recaptcha_public_key'));
		$pri = trim(as_opt('recaptcha_private_key'));

		$error = null;
		if (!strlen($pub) || !strlen($pri)) {
			require_once $this->directory.'recaptchalib.php';
			$error = 'To use reCAPTCHA, you must <a href="'.as_html(ReCaptcha::getSignupUrl()).'" target="_blank">sign up</a> to get these keys.';
		}

		$form = array(
			'ok' => $saved ? 'reCAPTCHA settings saved' : null,

			'fields' => array(
				'public' => array(
					'label' => 'reCAPTCHA Site key:',
					'value' => $pub,
					'tags' => 'name="recaptcha_public_key_field"',
				),

				'private' => array(
					'label' => 'reCAPTCHA Secret key:',
					'value' => $pri,
					'tags' => 'name="recaptcha_private_key_field"',
					'error' => $error,
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="recaptcha_save_button"',
				),
			),
		);

		return $form;
	}

	/**
	 * Only allow reCAPTCHA if the keys are set up (new reCAPTCHA has no special requirements)
	 */
	public function allow_captcha()
	{
		$pub = trim(as_opt('recaptcha_public_key'));
		$pri = trim(as_opt('recaptcha_private_key'));

		return strlen($pub) && strlen($pri);
	}

	/**
	 * reCAPTCHA HTML - we actually return nothing because the new reCAPTCHA requires 'explicit rendering'
	 * via JavaScript when we have multiple Captchas per page. It also auto-detects the user's language.
	 */
	public function form_html(&$as_content, $error)
	{
		$pub = as_opt('recaptcha_public_key');

		// onload handler
		$as_content['script_lines'][] = array(
			'function recaptcha_load(elemId) {',
			'  if (grecaptcha) {',
			'    grecaptcha.render(elemId, {',
			'      "sitekey": ' . as_js($pub),
			'    });',
			'  }',
			'}',
			'function recaptcha_onload() {',
			'  recaptcha_load("as_captcha_div_1");',
			'}',
		);

		$lang = urlencode(as_opt('site_language'));
		if (empty($lang))
			$lang = 'en';
		$as_content['script_src'][] = 'https://www.google.com/recaptcha/api.js?onload=recaptcha_onload&render=explicit&hl='.$lang;

		return '';
	}

	/**
	 * Check that the CAPTCHA was entered correctly. reCAPTCHA sets a long string in 'g-recaptcha-response'
	 * when the CAPTCHA is completed; we check that with the reCAPTCHA API.
	 */
	public function validate_post(&$error)
	{
		require_once $this->directory.'recaptchalib.php';

		if (ini_get('allow_url_fopen'))
			$recaptcha = new ReCaptcha(as_opt('recaptcha_private_key'));
		else
			$recaptcha = new ReCaptcha(as_opt('recaptcha_private_key'), new ReCaptchaSocketPostRequestMethod());

		$remoteIp = as_remote_ip_address();
		$userResponse = as_post_text('g-recaptcha-response');

		$recResponse = $recaptcha->verifyResponse($remoteIp, $userResponse);

		foreach ($recResponse->errorCodes as $code) {
			if (isset($this->errorCodeMessages[$code]))
				$error .= $this->errorCodeMessages[$code] . "\n";
		}

		return $recResponse->success;
	}
}
