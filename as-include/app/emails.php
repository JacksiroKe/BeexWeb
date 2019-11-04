<?php
/*
	AppSmata by Jackson Siro
	https://www.github.com/AppSmata/AppSmata/

	Description: Wrapper functions for sending email notifications to users


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: https://www.github.com/AppSmata/AppSmata/license.php
*/

if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once AS_INCLUDE_DIR . 'app/options.php';


/**
 * Suspend the sending of all email notifications via as_send_notification(...) if $suspend is true, otherwise
 * reinstate it. A counter is kept to allow multiple calls.
 * @param bool $suspend
 */
function as_suspend_notifications($suspend = true)
{
	global $as_notifications_suspended;

	$as_notifications_suspended += ($suspend ? 1 : -1);
}


/**
 * Send email to person with $userid and/or $email and/or $handle (null/invalid values are ignored or retrieved from
 * user database as appropriate). Email uses $subject and $body, after substituting each key in $subs with its
 * corresponding value, plus applying some standard substitutions such as ^site_title, ^handle and ^email.
 * @param $userid
 * @param $email
 * @param $handle
 * @param $subject
 * @param $body
 * @param $subs
 * @param bool $html
 * @return bool
 */
function as_send_notification($userid, $fullname, $email, $handle, $subject, $body, $subs)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	global $as_notifications_suspended;

	if ($as_notifications_suspended > 0)
		return false;

	require_once AS_INCLUDE_DIR . 'db/selects.php';
	require_once AS_INCLUDE_DIR . 'util/string.php';

	if (isset($userid)) {
		$needemail = !as_email_validate(@$email); // take from user if invalid, e.g. @ used in practice
		$needhandle = empty($handle);

		if ($needemail || $needhandle) {
			if (AS_FINAL_EXTERNAL_USERS) {
				if ($needhandle) {
					$handles = as_get_public_from_userids(array($userid));
					$handle = @$handles[$userid];
				}

				if ($needemail)
					$email = as_get_user_email($userid);

			} else {
				$useraccount = as_db_select_with_pending(
					array(
						'columns' => array('email', 'handle'),
						'source' => '^users WHERE userid = #',
						'arguments' => array($userid),
						'single' => true,
					)
				);

				if ($needhandle)
					$handle = @$useraccount['handle'];

				if ($needemail)
					$email = @$useraccount['email'];
			}
		}
	}

	if (isset($email) && as_email_validate($email)) {
		$subs['^site_title'] = as_opt('site_title');
		$subs['^handle'] = $handle;
		$subs['^email'] = $email;
		$subs['^open'] = "\n";
		$subs['^close'] = "\n";
		
		$email_body = as_email_html_container($fullname, nl2br($body));
		
		return as_send_email(array(
			'fromemail' => as_opt('from_email'),
			'fromname' => as_opt('site_title'),
			'toemail' => $email,
			'toname' => (($fullname == 'null' ) ? $handle : $fullname ),
			'subject' => strtr($subject, $subs),
			'body' => strtr($email_body, $subs),
			'html' => true,
		));
	}

	return false;
}


/**
 * Send the email based on the $params array - the following keys are required (some can be empty): fromemail,
 * fromname, toemail, toname, subject, body, html
 * @param $params
 * @return bool
 */
function as_send_email($params)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	// @error_log(print_r($params, true));

	require_once AS_INCLUDE_DIR . 'vendor/PHPMailer/class.phpmailer.php';
	require_once AS_INCLUDE_DIR . 'vendor/PHPMailer/class.smtp.php';

	$mailer = new PHPMailer();
	$mailer->CharSet = 'utf-8';

	$mailer->From = $params['fromemail'];
	$mailer->Sender = $params['fromemail'];
	$mailer->FromName = $params['fromname'];
	$mailer->addAddress($params['toemail'], $params['toname']);
	if (!empty($params['replytoemail'])) {
		$mailer->addReplyTo($params['replytoemail'], $params['replytoname']);
	}
	$mailer->Subject = $params['subject'];
	$mailer->Body = $params['body'];
	$mailer->isHTML(true);

	if (as_opt('smtp_active')) {
		$mailer->isSMTP();
		$mailer->Host = as_opt('smtp_address');
		$mailer->Port = as_opt('smtp_port');

		if (as_opt('smtp_secure')) {
			$mailer->SMTPSecure = as_opt('smtp_secure');
		} else {
			$mailer->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				),
			);
		}

		if (as_opt('smtp_authenticate')) {
			$mailer->SMTPAuth = true;
			$mailer->Username = as_opt('smtp_username');
			$mailer->Password = as_opt('smtp_password');
		}
	}

	$send_status = $mailer->send();
	if (!$send_status) {
		@error_log('PHP AppSmata email send error: ' . $mailer->ErrorInfo);
	}
	return $send_status;
}

function as_email_html_container($fullname, $body)
{
	$name = as_opt('site_title');
	$url = as_opt('site_url');
	$html = '<!doctype html>
			<html>
			  <head>
				<meta name="viewport" content="width=device-width" />
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<title>Mail from ' . $name . '</title>
				<style>  img { border: none; -ms-interpolation-mode: bicubic; max-width: 100%; } body { background-color: #f6f6f6; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; } table { border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; } table td { font-family: sans-serif; font-size: 14px; vertical-align: top; } .body { background-color: #f6f6f6; width: 100%; } .container { display: block; Margin: 0 auto !important;  max-width: 580px; padding: 10px; width: 580px; }  .content { box-sizing: border-box; display: block; Margin: 0 auto; max-width: 580px; padding: 10px; }.main { background: #fff; border-radius: 3px; width: 100%; } .wrapper { box-sizing: border-box; padding: 20px; } .footer { clear: both; padding-top: 10px; text-align: center; width: 100%; } .footer td, .footer p, .footer span, .footer a { color: #999999; font-size: 12px; text-align: center; } h1, h2, h3, h4 { color: #000000; font-family: sans-serif; font-weight: 400; line-height: 1.4; margin: 0; Margin-bottom: 30px; } h1 { font-size: 35px; font-weight: 300; text-align: center; text-transform: capitalize; } p, ul, ol { font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px; } p li, ul li, ol li { list-style-position: inside; margin-left: 5px; } a { color: #3498db; text-decoration: underline; } .btn { box-sizing: border-box; width: 100%; } .btn > tbody > tr > td { padding-bottom: 15px; } .btn table { width: auto; } .btn table td { background-color: #ffffff; border-radius: 5px; text-align: center; } .btn a { background-color: #ffffff; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; color: #3498db; cursor: pointer; display: inline-block; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-decoration: none; text-transform: capitalize; } .btn-primary table td { background-color: #3498db; } .btn-primary a { background-color: #3498db; border-color: #3498db; color: #ffffff; } .last { margin-bottom: 0; } .first { margin-top: 0; } .align-center { text-align: center; } .align-right { text-align: right; } .align-left { text-align: left; } .clear { clear: both; } .mt0 { margin-top: 0; } .mb0 { margin-bottom: 0; } .preheader { color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0; } .powered-by a { text-decoration: none; } hr { border: 0; border-bottom: 1px solid #f6f6f6; Margin: 20px 0; } @media only screen and (max-width: 620px) { table[class=body] h1 { font-size: 28px !important; margin-bottom: 10px !important; } table[class=body] p, table[class=body] ul, table[class=body] ol, table[class=body] td, table[class=body] span, table[class=body] a { font-size: 16px !important; } table[class=body] .wrapper, table[class=body] .article { padding: 10px !important; } table[class=body] .content { padding: 0 !important; } table[class=body] .container { padding: 0 !important; width: 100% !important; } table[class=body] .main { border-left-width: 0 !important; border-radius: 0 !important; border-right-width: 0 !important; } table[class=body] .btn table { width: 100% !important; } table[class=body] .btn a { width: 100% !important; } table[class=body] .img-responsive { height: auto !important; max-width: 100% !important; width: auto !important; }} @media all { .ExternalClass { width: 100%; } .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; } .apple-link a { color: inherit !important; font-family: inherit !important; font-size: inherit !important; font-weight: inherit !important; line-height: inherit !important; text-decoration: none !important; } .btn-primary table td:hover { background-color: #34495e !important; } .btn-primary a:hover { background-color: #34495e !important; border-color: #34495e !important; } } </style>
			  </head>';
		$html .= '<body class=""><table border="0" cellpadding="0" cellspacing="0" class="body"><tr><td>&nbsp;</td><td class="container"><div class="content">
			<table class="main">
			  <tr>
				<td class="wrapper">
				  <table border="0" cellpadding="0" cellspacing="0">
					<tr><td>
					<center><a href="' . $url . '"><img src="' . $url . 'as-media/appicon.png" style="width:100px;"></a></center></td>
					</tr>
					<tr>
					  <td>
						<p>Hello ' . (($fullname == 'null' ) ? 'There' : $fullname ) . ',</p>
						<p>' . $body . ' </p>
						  </td>
						</tr>
					  </table>
					</td>
				  </tr>';
		$html .= '</table>
					<div class="footer">
					  <table border="0" cellpadding="0" cellspacing="0">
						<tr>
						  <td class="content-block">
							<span class="apple-link">
						  </td>
						</tr>
						<tr>
						  <td class="content-block powered-by">
							<a href="' . $url . '">' . $name . '</a>.
						  </td>
						</tr>
					  </table>
					</div>
				  </div>
				</td>
				<td>&nbsp;</td>
			  </tr>
			</table>
		  </body>
		</html>';
		return $html;
}