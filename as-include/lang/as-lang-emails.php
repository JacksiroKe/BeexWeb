<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Language phrases for email notifications


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

return array(
	'a_commented_body' => "Your review on ^site_title has a new comment by ^c_handle:\n\n^open^c_content^close\n\nYour review was:\n\n^open^c_context^close\n\nYou may respond by adding your own comment:\n\n^url\n\nThank you,\n\n^site_title",
	'a_commented_subject' => 'Your ^site_title review has a new comment',

	'a_followed_body' => "Your review on ^site_title has a new related item by ^q_handle:\n\n^open^q_title^close\n\nYour review was:\n\n^open^a_content^close\n\nClick below to review the new item:\n\n^url\n\nThank you,\n\n^site_title",
	'a_followed_subject' => 'Your ^site_title review has a related item',

	'a_selected_body' => "Congratulations! Your review on ^site_title has been selected as the best by ^s_handle:\n\n^open^a_content^close\n\nThe item was:\n\n^open^q_title^close\n\nClick below to see your review:\n\n^url\n\nThank you,\n\n^site_title",
	'a_selected_subject' => 'Your ^site_title review has been selected!',

	'c_commented_body' => "A new comment by ^c_handle has been added after your comment on ^site_title:\n\n^open^c_content^close\n\nThe discussion is following:\n\n^open^c_context^close\n\nYou may respond by adding another comment:\n\n^url\n\nThank you,\n\n^site_title",
	'c_commented_subject' => 'Your ^site_title comment has been added to',

	'confirm_body' => "Please click below to confirm your email address for ^site_title.\n\n^url\n\nConfirmation code: ^code\n\n Thank you,\n^site_title",
	'confirm_subject' => '^site_title - Email Address Confirmation',

	'feedback_body' => "Comments:\n^message\n\nName:\n^name\n\nEmail:\n^email\n\nPrevious page:\n^previous\n\nUser:\n^url\n\nIP address:\n^ip\n\nBrowser:\n^browser",
	'feedback_subject' => '^ feedback',

	'flagged_body' => "A post by ^p_handle has received ^flags:\n\n^open^p_context^close\n\nClick below to see the post:\n\n^url\n\n\nClick below to review all flagged posts:\n\n^a_url\n\n\nThank you,\n\n^site_title",
	'flagged_subject' => '^site_title has a flagged post',

	'moderate_body' => "A post by ^p_handle requires your approval:\n\n^open^p_context^close\n\nClick below to approve or reject the post:\n\n^url\n\n\nClick below to review all queued posts:\n\n^a_url\n\n\nThank you,\n\n^site_title",
	'moderate_subject' => '^site_title moderation',

	'new_password_body' => "Your new password for ^site_title is below.\n\nPassword: ^password\n\nIt is recommended to change this password immediately after logging in.\n\nThank you,\n^site_title\n^url",
	'new_password_subject' => '^site_title - Your New Password',

	'private_message_body' => "You have been sent a private message by ^f_handle on ^site_title:\n\n^open^message^close\n\n^moreThank you,\n\n^site_title\n\n\nTo block private messages, visit your account page:\n^a_url",
	'private_message_info' => "More information about ^f_handle:\n\n^url\n\n",
	'private_message_reply' => "Click below to reply to ^f_handle by private message:\n\n^url\n\n",
	'private_message_subject' => 'Message from ^f_handle on ^site_title',

	'q_reviewed_body' => "Your item on ^site_title has been reviewed by ^a_handle:\n\n^open^a_content^close\n\nYour item was:\n\n^open^q_title^close\n\nIf you like this review, you may select it as the best:\n\n^url\n\nThank you,\n\n^site_title",
	'q_reviewed_subject' => 'Your ^site_title item was reviewed',

	'q_commented_body' => "Your item on ^site_title has a new comment by ^c_handle:\n\n^open^c_content^close\n\nYour item was:\n\n^open^c_context^close\n\nYou may respond by adding your own comment:\n\n^url\n\nThank you,\n\n^site_title",
	'q_commented_subject' => 'Your ^site_title item has a new comment',

	'q_posted_body' => "A new item has been posted by ^q_handle:\n\n^open^q_title\n\n^q_content^close\n\nClick below to see the item:\n\n^url\n\nThank you,\n\n^site_title",
	'q_posted_subject' => '^site_title has a new item',

	'remoderate_body' => "An edited post by ^p_handle requires your reapproval:\n\n^open^p_context^close\n\nClick below to approve or hide the edited post:\n\n^url\n\n\nClick below to review all queued posts:\n\n^a_url\n\n\nThank you,\n\n^site_title",
	'remoderate_subject' => '^site_title moderation',

	'reset_body' => "Please click below to reset your password for ^site_title.\n\n^url\n\nAlternatively, enter the code below into the field provided.\n\nCode: ^code\n\nIf you did not write to reset your password, please ignore this message.\n\nThank you,\n^site_title",
	'reset_subject' => '^site_title - Reset Forgotten Password',

	'to_handle_prefix' => "^,\n\n",

	'elevated_body_up' => "Congratulations! Because we have confidence in you, Your user profile on ^site_title has been elevated from being a  ^old_level to  ^new_level!\n\nClick below to check ^site_title under your new priviledges:\n\n^url\n\nThank you,\n\n^site_title",
	'elevated_body_down' => "Sorry! Because we have found you as ^old_level to be threathening the intergrity of ^site_title, we have to stripped you of your priviledges on ^site_title a little! You will still continue to participate on ^site_title with your remaining priviledges as a ^new_level. \n\nIf you find this to be a mistake please contact our admin via private message.\n\nClick below to check ^site_title under your new priviledges:\n\n^url\n\nThank you,\n\n^site_title",
	
	'elevated_subject_up' => '^site_title - Your profile level has been elevated!',
	'elevated_subject_down' => '^site_title - Your profile level has been lowered!',

	'u_signed up_body' => "A new user has signed up as ^u_handle.\n\nClick below to view the user profile:\n\n^url\n\nThank you,\n\n^site_title",
	'u_signed up_subject' => '^site_title has a new signed up user',
	'u_to_approve_body' => "A new user has signed up as ^u_handle.\n\nClick below to approve the user:\n\n^url\n\nClick below to review all users waiting for approval:\n\n^a_url\n\nThank you,\n\n^site_title",

	'u_approved_body' => "You can see your new user profile here:\n\n^url\n\nThank you,\n\n^site_title",
	'u_approved_subject' => 'Your ^site_title user has been approved',

	'wall_post_body' => "^f_handle has posted on your user wall at ^site_title:\n\n^open^post^close\n\nYou may respond to the post here:\n\n^url\n\nThank you,\n\n^site_title",
	'wall_post_subject' => 'Post on your ^site_title wall',

	'welcome_body' => "Thank you for taking your time to sign up on ^site_title.\n\n^custom^confirm\n\nYour signin details are as follows:\n\nUsername: ^handle\nEmail: ^email\n\nPlease keep this information safe for future reference.\n\nThank you,\n\n^site_title\n^url",
	'welcome_confirm' => "Please click below to confirm your email address.\n\n^url\n\n",
	'welcome_subject' => 'Welcome to ^site_title!',
	
	'new_business_body' => "Congratulations for setting up a new business on ^site_title.\n\nYour business details are as follows:\n\Name: ^business_title\n\nUsername: ^business_username\n\nLocation: ^business_location\n\nContacts: ^business_contact\n\nDescription: ^business_description\n\nFeel free to update this information if you that necessary.\n\nPlease click below to open your business profile page.\n\n^business_url\n\nThank you,\n\n^site_title\n^url",
	'new_business_subject' => 'Congratulations for setting up ^business_title Business!',
	
	'new_business_manager_body' => "Congratulations! One of the Managers of ^business_title business has added more priviledges to your profile on ^site_title. You are now a Manager of the business\n\nThe business details are as follows:\n\nBusiness name: ^business_title\nBusiness Username: ^business_username\n\nLocation: ^business_location\n\nContacts: ^business_contact\n\nDescription: ^business_description\n\nPlease click below to open this business's profile page.\n\n^business_url\n\nThank you,\n\n^site_title\n^url",
	'not_business_manager_body' => "Sorry! You are no longer a manager of ^business_title anymore. More information over this action will be provided to you in subsequent communications to you and over the email.\n\nIf you find this to be a mistake please contact our admin via private message.\n\n^url\n\nThank you,\n\n^site_title",
	
	'new_business_manager_subject' => 'Congrats! You are now a ^business_title Manager!',
	'not_business_manager_subject' => 'Sorry! You are no longer a ^business_title Manager!',

	'new_depart_body' => "Congratulations for setting up a new department for ^depart_business on ^site_title.\n\nYour department details are as follows:\n\Name: ^depart_title\n\nDescription: ^depart_description\n\nFeel free to update this information if you that necessary.\n\nPlease click below to open your department profile page.\n\n^depart_url\n\nThank you,\n\n^site_title\n^url",
	'new_depart_subject' => 'Congratulations for setting up ^depart_title Department!',
	
	'new_depart_manager_body' => "Congratulations! One of the Managers of the ^depart_title department in ^depart_business has added more priviledges to your profile on ^site_title. You are now a Manager of the department too. ^depart_description\n\nPlease click below to open this department's main page.\n\n^depart_url\n\nThank you,\n\n^site_title\n^url",
	
	'not_depart_manager_body' => "Sorry! You are no longer a manager of ^depart_title anymore. More information over this action will be provided to you in subsequent communications to you and over the email.\n\nIf you find this to be a mistake please contact our admin via private message.\n\n^url\n\nThank you,\n\n^site_title",
	
	'new_depart_manager_subject' => 'Congrats! You are now a ^depart_title Manager!',
	'not_depart_manager_subject' => 'Sorry! You are no longer a ^depart_title Manager!',

);
