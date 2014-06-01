<?php



/*
old actions and filters, to move into own loggers
*/
function old_logger_inits() {

		/** called on init: */

		// user login and logout
		add_action("wp_login", "simple_history_wp_login");
		add_action("wp_logout", "simple_history_wp_logout");

		// user failed login attempt to username that exists
		#$user = apply_filters('wp_authenticate_user', $user, $password);
		add_action("wp_authenticate_user", "sh_log_wp_authenticate_user", 10, 2);

		// user profile page modifications
		add_action("delete_user", "simple_history_delete_user");
		add_action("user_register", "simple_history_user_register");
		add_action("profile_update", "simple_history_profile_update");
	
		// options
		#add_action("updated_option", "simple_history_updated_option", 10, 3);
		#add_action("updated_option", "simple_history_updated_option2", 10, 2);
		#add_action("updated_option", "simple_history_updated_option3", 10, 1);
		#add_action("update_option", "simple_history_update_option", 10, 3);
	
		// plugin
		add_action("activated_plugin", "simple_history_activated_plugin");
		add_action("deactivated_plugin", "simple_history_deactivated_plugin");



		/** called on admin_init */
		// posts						 
		add_action("save_post", "simple_history_save_post");
		add_action("transition_post_status", "simple_history_transition_post_status", 10, 3);
		add_action("delete_post", "simple_history_delete_post");
										 
		// attachments/media			 
		add_action("add_attachment", "simple_history_add_attachment");
		add_action("edit_attachment", "simple_history_edit_attachment");
		add_action("delete_attachment", "simple_history_delete_attachment");
		
		// comments
		add_action("edit_comment", "simple_history_edit_comment");
		add_action("delete_comment", "simple_history_delete_comment");
		add_action("wp_set_comment_status", "simple_history_set_comment_status", 10, 2);

		// settings (all built in except permalinks)
		$arr_option_pages = array("general", "writing", "reading", "discussion", "media", "privacy");
		foreach ($arr_option_pages as $one_option_page_name) {
			$new_func = create_function('$capability', '
					return simple_history_add_update_option_page($capability, "'.$one_option_page_name.'");
				');
			add_filter("option_page_capability_{$one_option_page_name}", $new_func);
		}

		// settings page for permalinks
		add_action('check_admin_referer', "simple_history_add_update_option_page_permalinks", 10, 2);

		// core update = wordpress updates
		add_action( '_core_updated_successfully', array($this, "action_core_updated") );




}










/**
 * Old loggers/hooks are here
 * All things here are to be moved into own SimpleLogger classes
 */

function simple_history_edit_comment($comment_id) {
	
	$comment_data = get_commentdata($comment_id, 0, true);
	$comment_post_ID = $comment_data["comment_post_ID"];
	$post = get_post($comment_post_ID);
	$post_title = get_the_title($comment_post_ID);
	$excerpt = get_comment_excerpt($comment_id);
	$author = get_comment_author($comment_id);

	$str = sprintf( "$excerpt [" . __('From %1$s on %2$s') . "]", $author, $post_title );
	$str = urlencode($str);

	simple_history_add("action=edited&object_type=comment&object_name=$str&object_id=$comment_id");

}

function simple_history_delete_comment($comment_id) {
	
	$comment_data = get_commentdata($comment_id, 0, true);
	$comment_post_ID = $comment_data["comment_post_ID"];
	$post = get_post($comment_post_ID);
	$post_title = get_the_title($comment_post_ID);
	$excerpt = get_comment_excerpt($comment_id);
	$author = get_comment_author($comment_id);

	$str = sprintf( "$excerpt [" . __('From %1$s on %2$s') . "]", $author, $post_title );
	$str = urlencode($str);

	simple_history_add("action=deleted&object_type=comment&object_name=$str&object_id=$comment_id");

}

function simple_history_set_comment_status($comment_id, $new_status) {
	#echo "<br>new status: $new_status<br>"; // 0
	// $new_status hold (unapproved), approve, spam, trash
	$comment_data = get_commentdata($comment_id, 0, true);
	$comment_post_ID = $comment_data["comment_post_ID"];
	$post = get_post($comment_post_ID);
	$post_title = get_the_title($comment_post_ID);
	$excerpt = get_comment_excerpt($comment_id);
	$author = get_comment_author($comment_id);

	$action = "";
	if ("approve" == $new_status) {
		$action = 'approved';
	} elseif ("hold" == $new_status) {
		$action = 'unapproved';
	} elseif ("spam" == $new_status) {
		$action = 'marked as spam';
	} elseif ("trash" == $new_status) {
		$action = 'trashed';
	} elseif ("0" == $new_status) {
		$action = 'untrashed';
	}

	$action = urlencode($action);

	$str = sprintf( "$excerpt [" . __('From %1$s on %2$s') . "]", $author, $post_title );
	$str = urlencode($str);

	simple_history_add("action=$action&object_type=comment&object_name=$str&object_id=$comment_id");

}


function simple_history_add_attachment($attachment_id) {
	$post = get_post($attachment_id);
	$post_title = urlencode(get_the_title($post->ID));
	simple_history_add("action=added&object_type=attachment&object_id=$attachment_id&object_name=$post_title");

}
function simple_history_edit_attachment($attachment_id) {
	// is this only being called if the title of the attachment is changed?!
	$post = get_post($attachment_id);
	$post_title = urlencode(get_the_title($post->ID));
	simple_history_add("action=updated&object_type=attachment&object_id=$attachment_id&object_name=$post_title");
}
function simple_history_delete_attachment($attachment_id) {
	$post = get_post($attachment_id);
	$post_title = urlencode(get_the_title($post->ID));
	simple_history_add("action=deleted&object_type=attachment&object_id=$attachment_id&object_name=$post_title");
}

// user is updated
function simple_history_profile_update($user_id) {
	$user = get_user_by("id", $user_id);
	$user_nicename = urlencode($user->user_nicename);
	simple_history_add("action=updated&object_type=user&object_id=$user_id&object_name=$user_nicename");
}

// user is created
function simple_history_user_register($user_id) {
	$user = get_user_by("id", $user_id);
	$user_nicename = urlencode($user->user_nicename);
	simple_history_add("action=created&object_type=user&object_id=$user_id&object_name=$user_nicename");
}

// user is deleted
function simple_history_delete_user($user_id) {
	$user = get_user_by("id", $user_id);
	$user_nicename = urlencode($user->user_nicename);
	simple_history_add("action=deleted&object_type=user&object_id=$user_id&object_name=$user_nicename");
}

// user logs in
function simple_history_wp_login($user) {
	$current_user = wp_get_current_user();
	$user = get_user_by("login", $user);
	$user_nicename = urlencode($user->user_nicename);
	// if user id = null then it's because we are logged out and then no one is acutally loggin in.. like a.. ghost-user!
	if ($current_user->ID == 0) {
		$user_id = $user->ID;
	} else {
		$user_id = $current_user->ID;
	}
	simple_history_add("action=logged in&object_type=user&object_id=".$user->ID."&user_id=$user_id&object_name=$user_nicename");
}
// user logs out
function simple_history_wp_logout() {
	$current_user = wp_get_current_user();
	$current_user_id = $current_user->ID;
	$user_nicename = urlencode($current_user->user_nicename);
	simple_history_add("action=logged out&object_type=user&object_id=$current_user_id&object_name=$user_nicename");
}

function simple_history_delete_post($post_id) {
	if (wp_is_post_revision($post_id) == false) {
		$post = get_post($post_id);
		if ($post->post_status != "auto-draft" && $post->post_status != "inherit") {
			$post_title = urlencode(get_the_title($post->ID));
			simple_history_add("action=deleted&object_type=post&object_subtype=" . $post->post_type . "&object_id=$post_id&object_name=$post_title");
		}
	}
}

function simple_history_save_post($post_id) {

	if (wp_is_post_revision($post_id) == false) {
		// not a revision
		// it should also not be of type auto draft
		$post = get_post($post_id);
		if ($post->post_status != "auto-draft") {
		}
		
	}
}

// post has changed status
function simple_history_transition_post_status($new_status, $old_status, $post) {

	#echo "<br>From $old_status to $new_status";

	// From new to auto-draft <- ignore
	// From new to inherit <- ignore
	// From auto-draft to draft <- page/post created
	// From draft to draft
	// From draft to pending
	// From pending to publish
	# From pending to trash
	// if not from & to = same, then user has changed something
	//bonny_d($post); // regular post object
	if ($old_status == "auto-draft" && ($new_status != "auto-draft" && $new_status != "inherit")) {
		// page created
		$action = "created";
	} elseif ($new_status == "auto-draft" || ($old_status == "new" && $new_status == "inherit")) {
		// page...eh.. just leave it.
		return;
	} elseif ($new_status == "trash") {
		$action = "deleted";
	} else {
		// page updated. i guess.
		$action = "updated";
	}
	$object_type = "post";
	$object_subtype = $post->post_type;

	// Attempt to auto-translate post types*/
	// no, no longer, do it at presentation instead
	#$object_type = __( ucfirst ( $object_type ) );
	#$object_subtype = __( ucfirst ( $object_subtype ) );

	if ($object_subtype == "revision") {
		// don't log revisions
		return;
	}
	
	if (wp_is_post_revision($post->ID) === false) {
		// ok, no revision
		$object_id = $post->ID;
	} else {
		return; 
	}
	
	$post_title = get_the_title($post->ID);
	$post_title = urlencode($post_title);
	
	simple_history_add("action=$action&object_type=$object_type&object_subtype=$object_subtype&object_id=$object_id&object_name=$post_title");
}

// called when saving an options page
function simple_history_add_update_option_page($capability = NULL, $option_page = NULL) {

	$arr_options_names = array(
		"general" 		=> __("General Settings"),
		"writing"		=> __("Writing Settings"),
		"reading"		=> __("Reading Settings"),
		"discussion"	=> __("Discussion Settings"),
		"media"			=> __("Media Settings"),
		"privacy"		=> __("Privacy Settings")
	);
	
	$option_page_name = "";
	if (isset($arr_options_names[$option_page])) {
		$option_page_name = $arr_options_names[$option_page];
		simple_history_add("action=modified&object_type=settings page&object_id=$option_page&object_name=$option_page_name");
	}

	return $capability;
}

// called when updating permalinks
function simple_history_add_update_option_page_permalinks($action, $result) {
	
	if ("update-permalink" == $action) {
		$option_page_name = __("Permalink Settings");
		$option_page = "permalink";
		simple_history_add("action=modified&object_type=settings page&object_id=$option_page&object_name=$option_page_name");
	}

}

/**
 * Log failed login attempt to username that exists
 */
function sh_log_wp_authenticate_user($user, $password) {

	if ( ! wp_check_password($password, $user->user_pass, $user->ID) ) {
		
		// call __() to make translation exist
		__("failed to log in because they entered the wrong password", "simple-history");

		$description = "";
		$description .= "HTTP_USER_AGENT: " . $_SERVER["HTTP_USER_AGENT"];
		$description .= "\nHTTP_REFERER: " . $_SERVER["HTTP_REFERER"];
		$description .= "\nREMOTE_ADDR: " . $_SERVER["REMOTE_ADDR"];

		$args = array(
					"object_type" => "user",
					"object_name" => $user->user_login,
					"action" => "failed to log in because they entered the wrong password",
					"object_id" => $user->ID,
					"description" => $description
				);
		
		simple_history_add($args);

	}

	return $user;

}

function simple_history_update_option($option, $oldval, $newval) {

	if ($option == "active_plugins") {
	
		$debug = "\n";
		$debug .= "\nsimple_history_update_option()";
		$debug .= "\noption: $option";
		$debug .= "\noldval: " . print_r($oldval, true);
		$debug .= "\nnewval: " . print_r($newval, true);
	
		//  Returns an array containing all the entries from array1 that are not present in any of the other arrays. 
		// alltså:
		//	om newval är array1 och innehåller en rad så är den tillagd
		// 	om oldval är array1 och innhåller en rad så är den bortagen
		$diff_added = array_diff((array) $newval, (array) $oldval);
		$diff_removed = array_diff((array) $oldval, (array) $newval);
		$debug .= "\ndiff_added: " . print_r($diff_added, true);
		$debug .= "\ndiff_removed: " . print_r($diff_removed, true);
	}
}


/**
 * Plugin is activated
 * plugin_name is like admin-menu-tree-page-view/index.php
 */
function simple_history_activated_plugin($plugin_name) {

	// Fetch info about the plugin
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_name );
	
	if ( is_array( $plugin_data ) && ! empty( $plugin_data["Name"] ) ) {
		$plugin_name = urlencode( $plugin_data["Name"] );
	} else {
		$plugin_name = urlencode($plugin_name);
	}

	simple_history_add("action=activated&object_type=plugin&object_name=$plugin_name");
}

/**
 * Plugin is deactivated
 * plugin_name is like admin-menu-tree-page-view/index.php
 */
function simple_history_deactivated_plugin($plugin_name) {

	// Fetch info about the plugin
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_name );
	
	if ( is_array( $plugin_data ) && ! empty( $plugin_data["Name"] ) ) {
		$plugin_name = urlencode( $plugin_data["Name"] );
	} else {
		$plugin_name = urlencode($plugin_name);
	}
	
	simple_history_add("action=deactivated&object_type=plugin&object_name=$plugin_name");

}

// WordPress Core updated
function action_core_updated($wp_version) {
	simple_history_add("action=updated&object_type=wordpress_core&object_id=wordpress_core&object_name=".sprintf(__('WordPress %1$s', 'simple-history'), $wp_version));
}

