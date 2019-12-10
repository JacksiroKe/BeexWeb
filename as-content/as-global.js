/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Common Javascript for APS pages including posting and AJAX

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

// General page functions

function as_tableview(datatype, business)
{
	var contentview = document.getElementById('main_content');

	var params = {};
	params.data_type = datatype;
	params.business_id = business;

	as_ajax_post('tableview', params, function(lines) 
	{
		if (lines[0] == '1') {
			contentview.innerHTML = lines.slice(1).join('\n');			
			as_table_export();
			//as_show_waiting_after(elem, false);
		} else if (lines[0] == '0') {
			//as_show_waiting_after(elem, false);
		} else {
			as_ajax_error();
		}
	});
	//as_show_waiting_after(document.getElementById('similar'), true);

	return false;
}

function as_new_customer()
{
	var field_bsid = document.getElementById('business');
	var field_title = document.getElementById('title');
	var field_type = document.getElementById('type');
	var field_idno = document.getElementById('idnumber');
	var field_mobile = document.getElementById('mobile');
	var field_email = document.getElementById('email');
	var field_region = document.getElementById('region');
	var field_city = document.getElementById('city');
	var field_road = document.getElementById('road');

	var params = {};
	params.cust_bsid = field_bsid.value;
	params.cust_title = field_title.value;
	params.cust_type = field_type.value;
	params.cust_idnumber = field_idno.value;
	params.cust_mobile = field_mobile.value;
	params.cust_email = field_email.value;
	params.cust_region = field_region.value;
	params.cust_city = field_city.value;
	params.cust_road = field_road.value;
	
	as_ajax_post('newcustomer', params, function(lines) 
	{
		if (lines[0] == '1') {
			var refresh = document.getElementById('userresult');
			refresh.innerHTML = lines.slice(1).join('\n');
			field_title.value = '';
			field_type.value = '';
			field_idno.value = '';
			field_mobile.value = '';
			field_region.value = '';
			field_city.value = '';
			field_road.value = '';

			as_show_waiting_after(elem, false);
		} else if (lines[0] == '0') {
			as_show_waiting_after(elem, false);
		} else {
			as_ajax_error();
		}
	});
	//as_show_waiting_after(document.getElementById('userresult'), true);

	return false;
}

function as_select_county()
{
	var params = {};
	params.countyid = document.getElementById('county').value;
	as_ajax_post('countyselect', params, function(lines) {
		if (lines[0] == '1') {
			var resultsview = document.getElementById('bs_subcounty');
			resultsview.innerHTML = lines.slice(1).join('\n');
			//as_show_waiting_after(elem, false);
		} else if (lines[0] == '0') {
			as_show_waiting_after(elem, false);
		} else {
			as_ajax_error();
		}
	});
}

function as_select_subcounty()
{
	var params = {};
	params.subcountyid = document.getElementById('subcounty').value;
	params.towns_feedback = document.getElementById('townsfeedback').value;
	as_ajax_post('countyselect_sub', params, function(lines) {
		if (lines[0] == '1') {
			var resultsview = document.getElementById('bs_town');
			resultsview.innerHTML = lines.slice(1).join('\n');
			//as_show_waiting_after(elem, false);
		} else if (lines[0] == '0') {
			as_show_waiting_after(elem, false);
		} else {
			as_ajax_error();
		}
	});
}

function as_search_customer_change()
{
	var params = {};
	params.item_biz = document.getElementById('businessid').value;
	params.searchtext = document.getElementById('searchcustomer').value;
	document.getElementById('item_results').innerHTML = '';
	as_conceal('#order_preview');
	as_conceal('#item_search');
	if(params.searchtext.length > 3)
	{ 
		as_conceal('#order_item_search');

		as_ajax_post('searchcustomer', params, function(lines) {
			if (lines[0] == '1') {
				var resultsview = document.getElementById('search_customer_results');
				resultsview.innerHTML = lines.slice(1).join('\n');
			} else if (lines[0] == '0') {
				as_show_waiting_after(elem, false);
			} else {
				as_ajax_error();
			}
		});
	}
}

function as_select_customer(customerid)
{
	document.getElementById('selected_customer').value = customerid;
	as_reveal('#item_search');
	//document.getElementById('item_results').innerHTML = '';
	var params = {};
	params.customer = customerid;
	params.infotype = 'customer';
	as_ajax_post('getinfo', params, function(lines) {
		if (lines[0] == '1') {
			var resultsview = document.getElementById('customer_details');
			resultsview.innerHTML = lines.slice(1).join('\n');
			//as_show_waiting_after(elem, false);
		} else if (lines[0] == '0') {
			as_show_waiting_after(elem, false);
		} else {
			as_ajax_error();
		}
	});
}

function as_addto_order_form(item_id)
{
	var itemstr = item_id.split('_');
	//document.getElementById('selected_customer').value = itemstr[0];

	var formX = document.getElementById('order_preview');
	if (formX.style.display === "none") as_reveal('#order_preview');

	as_reveal('#order_preview');
	
	var params = {};
	params.item = itemstr[1];
	params.infotype = 'item';
	as_ajax_post('getinfo', params, function(lines) {
		if (lines[0] == '1') {
			var wishlistview = document.getElementById('wishlist');
			var amountdueview = document.getElementById('amountdue');
			wishlistview.innerHTML = lines.slice(1).join('\n');

			var wishlistview = document.getElementById('wishlist');
			var amountdueview = document.getElementById('amountdue');
			var fbstr = lines.slice(1).join('\n').split('xqx');
			wishlistview.innerHTML = fbstr[0];
			amountdueview.innerHTML = fbstr[1];
			//as_show_waiting_after(elem, false);
		} else if (lines[0] == '0') {
			as_show_waiting_after(elem, false);
		} else {
			as_ajax_error();
		}
	});
}

function as_search_user_change()
{
	var params = {};
	params.item_biz = document.getElementById('businessid').value;
	params.searchtext = document.getElementById('searchuser').value;
	if(params.searchtext.length > 3)
	{ 
		as_ajax_post('searchuser', params, function(lines) {
			if (lines[0] == '1') {
				var resultsview = document.getElementById('search_user_results');
				resultsview.innerHTML = lines.slice(1).join('\n');
			} else if (lines[0] == '0') {
				as_show_waiting_after(elem, false);
			} else {
				as_ajax_error();
			}
		});
	}
}

function as_search_user_change_d()
{
	var params = {};
	params.item_dept = document.getElementById('department_id').value;
	params.searchtext = document.getElementById('searchuser').value;
	if(params.searchtext.length > 3)
	{ 
		as_ajax_post('searchuser_d', params, function(lines) {
			if (lines[0] == '1') {
				var resultsview = document.getElementById('manager_results');
				resultsview.innerHTML = lines.slice(1).join('\n');
			} else if (lines[0] == '0') {
				as_show_waiting_after(elem, false);
			} else {
				as_ajax_error();
			}
		});
	}
}

function as_change_business_role(elem, businessid, userid)
{
	var params = {};
	params.manager = userid;
	params.business = businessid;
	params.newrole = document.getElementById(elem + '_role_' + userid).value;
	
	as_ajax_post('addmanager', params, function(lines) 
	{
		if (lines[0] == '1') {
			var feedback = document.getElementById('feeback_user_results');
			var listview = document.getElementById('list_results');
			var fbstr = lines.slice(1).join('\n').split('xqx');
			feedback.innerHTML = fbstr[0];
			listview.innerHTML = fbstr[1];
		} else if (lines[0] == '0') {
		} else {
			as_ajax_error();
		}
	});
	return false;
}

function as_change_business_role_d(userid)
{
	var params = {};
	params.manager = userid;
	params.department = document.getElementById('department_id').value;
	
	as_ajax_post('addmanager_d', params, function(lines) 
	{
		if (lines[0] == '1') {
			var feedback = document.getElementById('itemresults_' + userid);
			var listview = document.getElementById('manager_list');
			var fbstr = lines.slice(1).join('\n').split('xqx');
			feedback.innerHTML = fbstr[0];
			listview.innerHTML = fbstr[1];
			//as_show_waiting_after(elem, false);
		} else if (lines[0] == '0') {
			as_show_waiting_after(elem, false);
		} else {
			as_ajax_error();
		}
	});
	//as_show_waiting_after(document.getElementById('managers_feedback'), true);
	return false;
}

function as_search_item_change(resulttype)
{
	var params = {};
	params.result_type = resulttype;
	params.item_biz = document.getElementById('business_id').value;
	params.search_text = document.getElementById('search_item').value;

	if(params.search_text.length > 1)
	{ 
		as_ajax_post('searchitem', params, function(lines) {
			if (lines[0] == '1') {
				var simelem = document.getElementById('item_results');
				simelem.innerHTML = lines.slice(1).join('\n');
			} else if (lines[0] == '0') {
			} else {
				as_ajax_error();
			}
		});
	}
}

function as_show_quick_form(formid)
{
	var formX = document.getElementById(formid);
	if (formX.style.display === "none") as_reveal('#' + formid);
	else as_conceal('#' + formid);
}

function get_available_stock(elem, itemid)
{
	var oldstock = document.getElementById(elem + '_oldstock_' + itemid);
	var stockqty = document.getElementById(elem + '_quantity_' + itemid);
	var itemstock = document.getElementById(elem + '_available_' + itemid);
	var newstock = document.getElementById(elem + '_newstock_' + itemid);
	itemstock.innerHTML = newstock.value = parseInt(oldstock.value) + parseInt(stockqty.value);
}

function as_add_stock(elem, itemid)
{
	var newstock = document.getElementById(elem + '_newstock_' + itemid);
	var result = document.getElementById(elem + '_itemresults_' + itemid);
	var itemstock = document.getElementById(elem + '_available_' + itemid);
	var business = document.getElementById('business_id').value;
	var itemqty = document.getElementById(elem + '_quantity_' + itemid);
	var newactual = document.getElementById(elem + '_actual_' + itemid);

	var params = {};
	params.item_id = itemid;
	params.item_biz = business;
	params.item_actual = itemqty.value;
	params.item_available = newstock.value;
	params.item_bprice = document.getElementById(elem + '_bprice_' + itemid).value;
	params.item_sprice = document.getElementById(elem + '_sprice_' + itemid).value;
	params.item_type = document.getElementById(elem + '_type_' + itemid ).value;
	params.item_state = document.getElementById(elem + '_state_' + itemid ).value;
	
	as_ajax_post('addstock', params, function(lines) 
	{
		if (lines[0] == '1') {
			var fbstr = lines.slice(1).join('\n').split('xqx');
			result.innerHTML = fbstr[0];
			itemstock.innerHTML = fbstr[1];
			newactual.innerHTML = fbstr[2];
			//as_show_waiting_after(elem, false);
			tableview('stockview', business);
		} else if (lines[0] == '0') {
			//as_show_waiting_after(elem, false);
		} else {
			as_ajax_error();
		}
	});
	//as_show_waiting_after(document.getElementById('similar'), true);

	return false;
}

function as_username_change(value)
{
	as_ajax_post('searchuser', {namesearch: value}, function(lines) {
		if (lines[0] == '1') {
			if (lines[1].length) {
				as_tags_examples = lines[1];
				as_tag_hints(true);
			}

			if (lines.length > 2) {
				var simelem = document.getElementById('userresults');
				if (simelem)
					simelem.innerHTML = lines.slice(2).join('\n');
			}

		} else if (lines[0] == '0')
			alert(lines[1]);
		else
			as_ajax_error();
	});

	as_show_waiting_after(document.getElementById('userresults'), true);
}

function as_order_now(category, item, elem)
{
	var qtty = document.getElementById('quantity');
	var place = document.getElementById('address');
	var params = {};
	params.o_itemid = item;
	params.o_category = category;
	params.o_quantity = qtty.value;
	params.o_address = place.value;
	
	as_reveal('#placing', 'form');
	as_conceal('#as-buying', 'form');
	
	as_ajax_post('order', params, function(lines) {
			if (lines[0] == '1') {
				qtty.value = '';
				place.value = '';
				as_show_waiting_after(elem, false);
				as_reveal('#as-buying', 'form');
				as_conceal('#placing', 'form');
			} else if (lines[0] == '0') {
				as_show_waiting_after(elem, false);
				as_reveal('#as-buying', 'form');
				as_conceal('#placing', 'form');
			} else {
				as_ajax_error();
			}

		}
	);
	as_show_waiting_after(elem, false);

	return false;
}

function as_reveal(elem, type, callback)
{
	if (elem)
		$(elem).slideDown(400, callback);
}

function as_conceal(elem, type, callback)
{
	if (elem)
		$(elem).slideUp(400);
}

function as_set_inner_html(elem, type, html)
{
	if (elem)
		elem.innerHTML = html;
}

function as_set_outer_html(elem, type, html)
{
	if (elem) {
		var e = document.createElement('div');
		e.innerHTML = html;
		elem.parentNode.replaceChild(e.firstChild, elem);
	}
}

function as_show_waiting_after(elem, inside)
{
	if (elem && !elem.as_waiting_shown) {
		var w = document.getElementById('as-waiting-template');

		if (w) {
			var c = w.cloneNode(true);
			c.id = null;

			if (inside)
				elem.insertBefore(c, null);
			else
				elem.parentNode.insertBefore(c, elem.nextSibling);

			elem.as_waiting_shown = c;
		}
	}
}

function as_hide_waiting(elem)
{
	var c = elem.as_waiting_shown;

	if (c) {
		c.parentNode.removeChild(c);
		elem.as_waiting_shown = null;
	}
}

function as_like_click(elem)
{
	var ens = elem.name.split('_');
	var postid = ens[1];
	var like = parseInt(ens[2]);
	var code = elem.form.elements.code.value;
	var anchor = ens[3];

	as_ajax_post('like', {postid: postid, like: like, code: code},
		function(lines) {
			if (lines[0] == '1') {
				as_set_inner_html(document.getElementById('voting_' + postid), 'voting', lines.slice(1).join("\n"));

			} else if (lines[0] == '0') {
				var mess = document.getElementById('errorbox');

				if (!mess) {
					mess = document.createElement('div');
					mess.id = 'errorbox';
					mess.className = 'as-error';
					mess.innerHTML = lines[1];
					mess.style.display = 'none';
				}

				var postelem = document.getElementById(anchor);
				var e = postelem.parentNode.insertBefore(mess, postelem);
				as_reveal(e);

			} else
				as_ajax_error();
		}
	);

	return false;
}

function as_notice_click(elem)
{
	var ens = elem.name.split('_');
	var code = elem.form.elements.code.value;

	as_ajax_post('notice', {noticeid: ens[1], code: code},
		function(lines) {
			if (lines[0] == '1')
				as_conceal(document.getElementById('notice_' + ens[1]), 'notice');
			else if (lines[0] == '0')
				alert(lines[1]);
			else
				as_ajax_error();
		}
	);

	return false;
}

function as_favorite_click(elem)
{
	var ens = elem.name.split('_');
	var code = elem.form.elements.code.value;

	as_ajax_post('favorite', {entitytype: ens[1], entityid: ens[2], favorite: parseInt(ens[3]), code: code},
		function(lines) {
			if (lines[0] == '1')
				as_set_inner_html(document.getElementById('favoriting'), 'favoriting', lines.slice(1).join("\n"));
			else if (lines[0] == '0') {
				alert(lines[1]);
				as_hide_waiting(elem);
			} else
				as_ajax_error();
		}
	);

	as_show_waiting_after(elem, false);

	return false;
}

function as_ajax_post(operation, params, callback)
{
	$.extend(params, {as: 'ajax', as_operation: operation, as_root: as_root, as_request: as_request});

	$.post(as_root, params, function(response) {
		var header = 'AS_AJAX_RESPONSE';
		var headerpos = response.indexOf(header);

		if (headerpos >= 0)
			callback(response.substr(headerpos + header.length).replace(/^\s+/, '').split("\n"));
		else
			callback([]);

	}, 'text').fail(function(jqXHR) {
		if (jqXHR.readyState > 0)
			callback([])
	});
}

function as_ajax_error()
{
	alert('Unexpected response from server - please try again or switch off Javascript.');
}

function as_display_rule_show(target, show, first)
{
	var e = document.getElementById(target);
	if (e) {
		if (first || e.nodeName == 'SPAN')
			e.style.display = (show ? '' : 'none');
		else if (show)
			$(e).fadeIn();
		else
			$(e).fadeOut();
	}
}


// Article page actions

var as_element_revealed = null;

function as_toggle_element(elem)
{
	var e = elem ? document.getElementById(elem) : null;

	if (e && e.as_disabled)
		e = null;

	if (e && (as_element_revealed == e)) {
		as_conceal(as_element_revealed, 'form');
		as_element_revealed = null;

	} else {
		if (as_element_revealed)
			as_conceal(as_element_revealed, 'form');

		if (e) {
			if (e.as_load && !e.as_loaded) {
				e.as_load();
				e.as_loaded = true;
			}

			if (e.as_show)
				e.as_show();

			as_reveal(e, 'form', function() {
				var t = $(e).offset().top;
				var h = $(e).height() + 16;
				var wt = $(window).scrollTop();
				var wh = $(window).height();

				if ((t < wt) || (t > (wt + wh)))
					as_scroll_page_to(t);
				else if ((t + h) > (wt + wh))
					as_scroll_page_to(t + h - wh);

				if (e.as_focus)
					e.as_focus();
			});
		}

		as_element_revealed = e;
	}

	return !(e || !elem); // failed to find item
}


function as_submit_review(articleid, elem)
{
	var params = as_form_params('a_form');

	params.a_articleid = articleid;

	as_ajax_post('review', params,
		function(lines) {
			if (lines[0] == '1') {
				if (lines[1] < 1) {
					var b = document.getElementById('q_doreview');
					if (b)
						b.style.display = 'none';
				}

				var t = document.getElementById('a_list_title');
				as_set_inner_html(t, 'a_list_title', lines[2]);
				as_reveal(t, 'a_list_title');

				var e = document.createElement('div');
				e.innerHTML = lines.slice(3).join("\n");

				var c = e.firstChild;
				c.style.display = 'none';

				var l = document.getElementById('a_list');
				l.insertBefore(c, l.firstChild);

				var a = document.getElementById('anew');
				a.as_disabled = true;

				as_reveal(c, 'review');
				as_conceal(a, 'form');

			} else if (lines[0] == '0') {
				document.forms['a_form'].submit();

			} else {
				as_ajax_error();
			}
		}
	);

	as_show_waiting_after(elem, false);

	return false;
}

function as_submit_comment(articleid, parentid, elem)
{
	var params = as_form_params('c_form_' + parentid);

	params.c_articleid = articleid;
	params.c_parentid = parentid;

	as_ajax_post('comment', params,
		function(lines) {

			if (lines[0] == '1') {
				var l = document.getElementById('c' + parentid + '_list');
				l.innerHTML = lines.slice(2).join("\n");
				l.style.display = '';

				var a = document.getElementById('c' + parentid);
				a.as_disabled = true;

				var c = document.getElementById(lines[1]); // id of comment
				if (c) {
					c.style.display = 'none';
					as_reveal(c, 'comment');
				}

				as_conceal(a, 'form');

			} else if (lines[0] == '0') {
				document.forms['c_form_' + parentid].submit();

			} else {
				as_ajax_error();
			}

		}
	);

	as_show_waiting_after(elem, false);

	return false;
}

function as_review_click(reviewid, articleid, target)
{
	var params = {};

	params.reviewid = reviewid;
	params.articleid = articleid;
	params.code = target.form.elements.code.value;
	params[target.name] = target.value;

	as_ajax_post('click_a', params,
		function(lines) {
			if (lines[0] == '1') {
				as_set_inner_html(document.getElementById('a_list_title'), 'a_list_title', lines[1]);

				var l = document.getElementById('a' + reviewid);
				var h = lines.slice(2).join("\n");

				if (h.length)
					as_set_outer_html(l, 'review', h);
				else
					as_conceal(l, 'review');

			} else {
				target.form.elements.as_click.value = target.name;
				target.form.submit();
			}
		}
	);

	as_show_waiting_after(target, false);

	return false;
}

function as_comment_click(commentid, articleid, parentid, target)
{
	var params = {};

	params.commentid = commentid;
	params.articleid = articleid;
	params.parentid = parentid;
	params.code = target.form.elements.code.value;
	params[target.name] = target.value;

	as_ajax_post('click_c', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('c' + commentid);
				var h = lines.slice(1).join("\n");

				if (h.length)
					as_set_outer_html(l, 'comment', h);
				else
					as_conceal(l, 'comment');

			} else {
				target.form.elements.as_click.value = target.name;
				target.form.submit();
			}
		}
	);

	as_show_waiting_after(target, false);

	return false;
}

function as_show_comments(articleid, parentid, elem)
{
	var params = {};

	params.c_articleid = articleid;
	params.c_parentid = parentid;

	as_ajax_post('show_cs', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('c' + parentid + '_list');
				l.innerHTML = lines.slice(1).join("\n");
				l.style.display = 'none';
				as_reveal(l, 'comments');

			} else {
				as_ajax_error();
			}
		}
	);

	as_show_waiting_after(elem, true);

	return false;
}

function as_form_params(formname)
{
	var es = document.forms[formname].elements;
	var params = {};

	for (var i = 0; i < es.length; i++) {
		var e = es[i];
		var t = (e.type || '').toLowerCase();

		if (((t != 'checkbox') && (t != 'radio')) || e.checked)
			params[e.name] = e.value;
	}

	return params;
}

function as_scroll_page_to(scroll)
{
	$('html,body').animate({scrollTop: scroll}, 400);
}


// Ask form

function as_title_change(value)
{
	as_ajax_post('writetitle', {title: value}, function(lines) {
		if (lines[0] == '1') {
			if (lines[1].length) {
				as_tags_examples = lines[1];
				as_tag_hints(true);
			}

			if (lines.length > 2) {
				var simelem = document.getElementById('similar');
				if (simelem)
					simelem.innerHTML = lines.slice(2).join('\n');
			}

		} else if (lines[0] == '0')
			alert(lines[1]);
		else
			as_ajax_error();
	});

	as_show_waiting_after(document.getElementById('similar'), true);
}

function as_html_unescape(html)
{
	return html.replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
}

function as_html_escape(text)
{
	return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function as_tag_click(link)
{
	var elem = document.getElementById('tags');
	var parts = as_tag_typed_parts(elem);

	// removes any HTML tags and ampersand
	var tag = as_html_unescape(link.innerHTML.replace(/<[^>]*>/g, ''));

	var separator = as_tag_onlycomma ? ', ' : ' ';

	// replace if matches typed, otherwise append
	var newvalue = (parts.typed && (tag.toLowerCase().indexOf(parts.typed.toLowerCase()) >= 0))
		? (parts.before + separator + tag + separator + parts.after + separator) : (elem.value + separator + tag + separator);

	// sanitize and set value
	if (as_tag_onlycomma)
		elem.value = newvalue.replace(/[\s,]*,[\s,]*/g, ', ').replace(/^[\s,]+/g, '');
	else
		elem.value = newvalue.replace(/[\s,]+/g, ' ').replace(/^[\s,]+/g, '');

	elem.focus();
	as_tag_hints();

	return false;
}

function as_tag_hints(skipcomplete)
{
	var elem = document.getElementById('tags');
	var html = '';
	var completed = false;

	// first try to auto-complete
	if (as_tags_complete && !skipcomplete) {
		var parts = as_tag_typed_parts(elem);

		if (parts.typed) {
			html = as_tags_to_html((as_html_unescape(as_tags_examples + ',' + as_tags_complete)).split(','), parts.typed.toLowerCase());
			completed = html ? true : false;
		}
	}

	// otherwise show examples
	if (as_tags_examples && !completed)
		html = as_tags_to_html((as_html_unescape(as_tags_examples)).split(','), null);

	// set title visiblity and hint list
	document.getElementById('tag_examples_title').style.display = (html && !completed) ? '' : 'none';
	document.getElementById('tag_complete_title').style.display = (html && completed) ? '' : 'none';
	document.getElementById('tag_hints').innerHTML = html;
}

function as_tags_to_html(tags, matchlc)
{
	var html = '';
	var added = 0;
	var tagseen = {};

	for (var i = 0; i < tags.length; i++) {
		var tag = tags[i];
		var taglc = tag.toLowerCase();

		if (!tagseen[taglc]) {
			tagseen[taglc] = true;

			if ((!matchlc) || (taglc.indexOf(matchlc) >= 0)) { // match if necessary
				if (matchlc) { // if matching, show appropriate part in bold
					var matchstart = taglc.indexOf(matchlc);
					var matchend = matchstart + matchlc.length;
					inner = '<span style="font-weight:normal;">' + as_html_escape(tag.substring(0, matchstart)) + '<b>' +
						as_html_escape(tag.substring(matchstart, matchend)) + '</b>' + as_html_escape(tag.substring(matchend)) + '</span>';
				} else // otherwise show as-is
					inner = as_html_escape(tag);

				html += as_tag_template.replace(/\^/g, inner.replace('$', '$$$$')) + ' '; // replace ^ in template, escape $s

				if (++added >= as_tags_max)
					break;
			}
		}
	}

	return html;
}

function as_caret_from_end(elem)
{
	if (document.selection) { // for IE
		elem.focus();
		var sel = document.selection.createRange();
		sel.moveStart('character', -elem.value.length);

		return elem.value.length - sel.text.length;

	} else if (typeof (elem.selectionEnd) != 'undefined') // other browsers
		return elem.value.length - elem.selectionEnd;

	else // by default return safest value
		return 0;
}

function as_tag_typed_parts(elem)
{
	var caret = elem.value.length - as_caret_from_end(elem);
	var active = elem.value.substring(0, caret);
	var passive = elem.value.substring(active.length);

	// if the caret is in the middle of a word, move the end of word from passive to active
	if (
		active.match(as_tag_onlycomma ? /[^\s,][^,]*$/ : /[^\s,]$/) &&
		(adjoinmatch = passive.match(as_tag_onlycomma ? /^[^,]*[^\s,][^,]*/ : /^[^\s,]+/))
		) {
		active += adjoinmatch[0];
		passive = elem.value.substring(active.length);
	}

	// find what has been typed so far
	var typedmatch = active.match(as_tag_onlycomma ? /[^\s,]+[^,]*$/ : /[^\s,]+$/) || [''];

	return {before: active.substring(0, active.length - typedmatch[0].length), after: passive, typed: typedmatch[0]};
}

function as_category_select(idprefix, startpath)
{
	var startval = startpath ? startpath.split("/") : [];
	var setdescnow = true;

	for (var l = 0; l <= as_cat_maxdepth; l++) {
		var elem = document.getElementById(idprefix + '_' + l);

		if (elem) {
			if (l) {
				if (l < startval.length && startval[l].length) {
					var val = startval[l];

					for (var j = 0; j < elem.options.length; j++)
						if (elem.options[j].value == val)
							elem.selectedIndex = j;
				} else
					var val = elem.options[elem.selectedIndex].value;
			} else
				val = '';

			if (elem.as_last_sel !== val) {
				elem.as_last_sel = val;

				var subelem = document.getElementById(idprefix + '_' + l + '_sub');
				if (subelem)
					subelem.parentNode.removeChild(subelem);

				if (val.length || (l == 0)) {
					subelem = elem.parentNode.insertBefore(document.createElement('span'), elem.nextSibling);
					subelem.id = idprefix + '_' + l + '_sub';
					as_show_waiting_after(subelem, true);

					as_ajax_post('category', {categoryid: val},
						(function(elem, l) {
							return function(lines) {
								var subelem = document.getElementById(idprefix + '_' + l + '_sub');
								if (subelem)
									subelem.parentNode.removeChild(subelem);

								if (lines[0] == '1') {
									elem.as_cat_desc = lines[1];

									var addedoption = false;

									if (lines.length > 2) {
										subelem = elem.parentNode.insertBefore(document.createElement('span'), elem.nextSibling);
										subelem.id = idprefix + '_' + l + '_sub';
										subelem.innerHTML = ' ';

										var newelem = elem.cloneNode(false);

										newelem.name = newelem.id = idprefix + '_' + (l + 1);
										newelem.options.length = 0;

										if (l ? as_cat_allownosub : as_cat_allownone)
											newelem.options[0] = new Option(l ? '' : elem.options[0].text, '', true, true);

										for (var i = 2; i < lines.length; i++) {
											var parts = lines[i].split('/');

											if (String(as_cat_exclude).length && (String(as_cat_exclude) == parts[0]))
												continue;

											newelem.options[newelem.options.length] = new Option(parts.slice(1).join('/'), parts[0]);
											addedoption = true;
										}

										if (addedoption) {
											subelem.appendChild(newelem);
											as_category_select(idprefix, startpath);

										}

										if (l == 0)
											elem.style.display = 'none';
									}

									if (!addedoption)
										set_category_description(idprefix);

								} else if (lines[0] == '0')
									alert(lines[1]);
								else
									as_ajax_error();
							}
						})(elem, l)
					);

					setdescnow = false;
				}

				break;
			}
		}
	}

	if (setdescnow)
		set_category_description(idprefix);
}

function set_category_description(idprefix)
{
	var n = document.getElementById(idprefix + '_note');

	if (n) {
		desc = '';

		for (var l = 1; l <= as_cat_maxdepth; l++) {
			var elem = document.getElementById(idprefix + '_' + l);

			if (elem && elem.options[elem.selectedIndex].value.length)
				desc = elem.as_cat_desc;
		}

		n.innerHTML = desc;
	}
}

// User functions

function as_submit_wall_post(elem, morelink)
{
	var params = {};

	params.message = document.forms.wallpost.message.value;
	params.handle = document.forms.wallpost.handle.value;
	params.start = document.forms.wallpost.start.value;
	params.code = document.forms.wallpost.code.value;
	params.morelink = morelink ? 1 : 0;

	as_ajax_post('wallpost', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('wallmessages');
				l.innerHTML = lines.slice(2).join("\n");

				var c = document.getElementById(lines[1]); // id of new message
				if (c) {
					c.style.display = 'none';
					as_reveal(c, 'wallpost');
				}

				document.forms.wallpost.message.value = '';
				as_hide_waiting(elem);

			} else if (lines[0] == '0') {
				document.forms.wallpost.as_click.value = elem.name;
				document.forms.wallpost.submit();

			} else {
				as_ajax_error();
			}
		}
	);

	as_show_waiting_after(elem, false);

	return false;
}

function as_wall_post_click(messageid, target)
{
	var params = {};

	params.messageid = messageid;
	params.handle = document.forms.wallpost.handle.value;
	params.start = document.forms.wallpost.start.value;
	params.code = document.forms.wallpost.code.value;

	params[target.name] = target.value;

	as_ajax_post('click_wall', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('m' + messageid);
				var h = lines.slice(1).join("\n");

				if (h.length)
					as_set_outer_html(l, 'wallpost', h);
				else
					as_conceal(l, 'wallpost');

			} else {
				document.forms.wallpost.as_click.value = target.name;
				document.forms.wallpost.submit();
			}
		}
	);

	as_show_waiting_after(target, false);

	return false;
}

function as_pm_click(messageid, target, box)
{
	var params = {};

	params.messageid = messageid;
	params.box = box;
	params.handle = document.forms.pmessage.handle.value;
	params.start = document.forms.pmessage.start.value;
	params.code = document.forms.pmessage.code.value;

	params[target.name] = target.value;

	as_ajax_post('click_pm', params,
		function(lines) {
			if (lines[0] == '1') {
				var l = document.getElementById('m' + messageid);
				var h = lines.slice(1).join("\n");

				if (h.length)
					as_set_outer_html(l, 'pmessage', h);
				else
					as_conceal(l, 'pmessage');

			} else {
				document.forms.pmessage.as_click.value = target.name;
				document.forms.pmessage.submit();
			}
		}
	);

	as_show_waiting_after(target, false);

	return false;
}

function as_table_export()
{
	var table = $('#as-table').DataTable( {
		"lengthMenu": [[25,50,100,500, -1], [25,50,100,500, "All"]],
		dom: 'Bfrtlip',
		"order": [[ 0, "asc" ]],

		colVis: {
			order: 'alpha',
			exclude: [ 0 ],
			restore: "Restore",
			showAll: "Show all",
			showNone: "Show none"
		},
		buttons: [
			{
				extend: 'copyHtml5',
				exportOptions: {
					columns: ':visible'
				}
			},
			{
				extend: 'excelHtml5',
				exportOptions: {
					columns: ':visible'
				}
			},
			{
				extend: 'csvHtml5',
				exportOptions: {
					columns: ':visible'
				}
			},
			{
				extend: 'pdfHtml5',
				exportOptions: {
					columns: ':visible'
				}
			},
			'colvis'
		],
		aoColumnDefs: [
			{ aTargets: [0], bSortable: false }
		],
	});
	
	// Apply the filter
	$("#as-table tfoot input").on( 'keyup change', function () {
		table
			.column( $(this).parent().index()+':visible' )
			.search( this.value )
			.draw();
	} );
	
	// select checkboxes 
	$('#check-button').click(function(event) {
		$('.chk-item').each(function() {
			this.checked = true;
		});
		$('#check-button').hide();
		$('#uncheck-button').show();
		event.preventDefault();
	});
	$('#uncheck-button').click(function(event) {
		$('.chk-item').each(function() {
			this.checked = false;					  
		});	
		$('#uncheck-button').hide();
		$('#check-button').show();	
		event.preventDefault();
	});
}

$(document).ready(function() {
	as_table_export();
} );

(function ($) {

})(jQuery);
