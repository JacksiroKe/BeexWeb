<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Development tool to see which language phrases are missing or unused


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

define('AS_BASE_DIR', dirname(dirname(empty($_SERVER['SCRIPT_FILENAME']) ? __FILE__ : $_SERVER['SCRIPT_FILENAME'])) . '/');

require 'as-base.php';
require_once AS_INCLUDE_DIR . 'app/users.php';

if (as_get_logged_in_level() < AS_USER_LEVEL_ADMIN)
	as_redirect('admin/general', null, as_opt('site_url'));

header('Content-type: text/html; charset=utf-8');

?><!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>AppSmata Language Check</title>
		<style>
			body {
				font-family: arial;
				font-size: 12px;
			}
			code {font-size: 125%;}
			.severe {color: #b83939;}
			.warning {color: #d2930d;}
			.includes {color: #999;}
		</style>
	</head>
	<body>
<?php

function get_phrase_substitutions($phrase)
{
	$substitutions = array();

	if (preg_match_all('/\^(([0-9]+)|([a-z_]+)|)/', $phrase, $matches)) {
		foreach ($matches[0] as $match) {
			@$substitutions[$match]++;
		}
	}

	return $substitutions;
}

echo '<code class="severe">Red = important to review.</code><br>';
echo '<code class="warning">Orange = probably safe to ignore.</code>';

echo '<h1>Checking US English files in <code>as-include</code>...</h1>';

$includefiles = array_merge(glob(AS_INCLUDE_DIR . 'as-*.php'), glob(AS_INCLUDE_DIR . '*/as-*.php'), glob(AS_PLUGIN_DIR . '*/as-*.php'));

$definite = array();
$probable = array();
$possible = array();
$defined = array();
$english = array();
$backmap = array();
$substitutions = array();

output_start_includes();

foreach ($includefiles as $includefile) {
	$contents = file_get_contents($includefile);

	preg_match_all('/as_lang[a-z_]*\s*\(\s*[\'\"]([a-z]+)\/([0-9a-z_]+)[\'\"]/', $contents, $matches, PREG_SET_ORDER);

	foreach ($matches as $matchparts) {
		if ($matchparts[2] == 'date_month_') { // special case for month names
			for ($month = 1; $month <= 12; $month++) {
				@$definite[$matchparts[1]][$matchparts[2] . $month]++;
			}

		} else
			@$definite[$matchparts[1]][$matchparts[2]]++;
	}

	preg_match_all('/[\'\"]([a-z]+)\/([0-9a-z_]+)[\'\"]/', $contents, $matches, PREG_SET_ORDER);

	foreach ($matches as $matchparts) {
		@$probable[$matchparts[1]][$matchparts[2]]++;
	}

	if (preg_match('|/as-include/as-lang-([a-z]+)\.php$|', $includefile, $matches)) { // it's a lang file
		$prefix = $matches[1];

		output_reading_include($includefile);
		$phrases = @include $includefile;

		foreach ($phrases as $key => $value) {
			@$defined[$prefix][$key]++;
			$english[$prefix][$key] = $value;
			$backmap[$value][] = array('prefix' => $prefix, 'key' => $key);
			$substitutions[$prefix][$key] = get_phrase_substitutions($value);
		}

	} else { // it's a different file
		preg_match_all('/[\'\"\/]([0-9a-z_]+)[\'\"]/', $contents, $matches, PREG_SET_ORDER);

		foreach ($matches as $matchparts) {
			@$possible[$matchparts[1]]++;
		}
	}
}

output_finish_includes();

foreach ($definite as $key => $valuecount) {
	foreach ($valuecount as $value => $count) {
		if (!@$defined[$key][$value]) {
			output_lang_issue($key, $value, 'used by ' . $count . ' file/s but not defined');
		}
	}
}

foreach ($defined as $key => $valuecount) {
	foreach ($valuecount as $value => $count) {
		if (!@$definite[$key][$value] && !@$probable[$key][$value] && !@$possible[$value]) {
			output_lang_issue($key, $value, 'defined but apparently not used');
		}
	}
}

foreach ($backmap as $phrase => $where) {
	if (count($where) > 1) {
		foreach ($where as $onewhere) {
			output_lang_issue($onewhere['prefix'], $onewhere['key'], 'contains the shared phrase "' . $phrase . '"', false);
		}
	}
}

require_once AS_INCLUDE_DIR . 'app/admin.php';

$languages = as_admin_language_options();
unset($languages['']);

foreach ($languages as $code => $language) {
	echo '<h1>Checking ' . $language . ' files in <code>as-lang/' . $code . '</code>...</h1>';

	$langdefined = array();
	$langdifferent = array();
	$langsubstitutions = array();
	$langincludefiles = glob(AS_LANG_DIR . $code . '/as-*.php');
	$langnewphrases = array();

	output_start_includes();

	foreach ($langincludefiles as $langincludefile) {
		if (preg_match('/as-lang-([a-z]+)\.php$/', $langincludefile, $matches)) { // it's a lang file
			$prefix = $matches[1];

			output_reading_include($langincludefile);
			$phrases = @include $langincludefile;

			foreach ($phrases as $key => $value) {
				@$langdefined[$prefix][$key]++;
				$langdifferent[$prefix][$key] = ($value != @$english[$prefix][$key]);
				$langsubstitutions[$prefix][$key] = get_phrase_substitutions($value);
			}
		}
	}

	output_finish_includes();

	foreach ($langdefined as $key => $valuecount) {
		foreach ($valuecount as $value => $count) {
			if (!@$defined[$key][$value]) {
				output_lang_issue($key, $value, 'defined but not in US English files');

			} elseif (!$langdifferent[$key][$value]) {
				output_lang_issue($key, $value, 'identical to US English files', false);

			} else {
				foreach ($substitutions[$key][$value] as $substitution => $subcount) {
					if (!@$langsubstitutions[$key][$value][$substitution])
						output_lang_issue($key, $value, 'omitted the substitution ' . $substitution);
					elseif ($subcount > @$langsubstitutions[$key][$value][$substitution])
						output_lang_issue($key, $value, 'has fewer of the substitution ' . $substitution);
				}
			}
		}
	}

	foreach ($defined as $key => $valuecount) {
		$showaserror = !(($key == 'admin') || ($key == 'options') || ($code == 'en-GB'));

		if (@$langdefined[$key]) {
			if (count($langdefined[$key]) < (count($valuecount) / 2)) { // only a few phrases defined
				output_lang_issue($key, null, 'few translations provided so will use US English defaults', $showaserror);

			} else {
				foreach ($valuecount as $value => $count) {
					if (!@$langdefined[$key][$value]) {
						output_lang_issue($key, $value, 'undefined so will use US English defaults', $showaserror);
						$langnewphrases[$key][$value] = $english[$key][$value];
					}
				}
			}
		} else {
			output_lang_issue($key, null, 'no translations provided so will use US English defaults', $showaserror);
		}
	}

	foreach ($langnewphrases as $prefix => $phrases) {
		echo '<h2>' . $language . ' phrases to add to <code>as-lang/' . $code . '/as-lang-' . $prefix . '.php</code>:</h2>';

		echo 'Copy and paste this into the middle of <code>as-lang/' . $code . '/as-lang-' . $prefix . '.php</code> then translate the right-hand side after the <code>=></code> symbol.';

		echo '<pre>';

		foreach ($phrases as $key => $value) {
			echo '<span style="font-size:25%;">' . "\t\t</span>'" . $key . "' => \"" . strtr($value, array('\\' => '\\\\', '"' => '\"', '$' => '\$', "\n" => '\n', "\t" => '\t')) . "\",\n";
		}

		echo '</pre>';
	}
}


function output_lang_issue($prefix, $key, $issue, $error = true)
{
	$colorClass = $error ? 'severe' : 'warning';

	$htmlKey = strlen($key) > 0 ? "'<strong>" . as_html($key) . "</strong>'" : '';

	echo sprintf(
		'<code class="%s">as-lang-<strong>%s</strong>.php:%s</code> &nbsp; %s<br>',
		$colorClass, as_html($prefix), $htmlKey, as_html($issue)
	);
}


function output_start_includes()
{
	global $oneread;

	$oneread = false;

	echo '<p class="includes">Reading: ';
}


function output_reading_include($file)
{
	global $oneread;

	echo ($oneread ? ', ' : '') . htmlspecialchars(basename($file));
	flush();

	$oneread = true;
}


function output_finish_includes()
{
	echo '</p>';
}


echo '<h1>Finished scanning for problems!</h1>';

?>

	</body>
</html>
