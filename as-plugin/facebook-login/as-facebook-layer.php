<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	File: as-plugin/facebook-login/as-facebook-layer.php
	Description: Theme layer class for mouseover layer plugin


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

class as_html_theme_layer extends as_html_theme_base
{
	public function head_css()
	{
		as_html_theme_base::head_css();

		if (strlen(as_opt('facebook_app_id')) && strlen(as_opt('facebook_app_secret'))) {
			$this->output(
				'<style>',
				'.fb-login-button.fb_iframe_widget.fb_hide_iframes span {display:none;}',
				'</style>'
			);
		}
	}
}
