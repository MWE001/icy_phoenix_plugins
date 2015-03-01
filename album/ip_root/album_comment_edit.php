<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
*
* @Extra credits for this file
* Smartor (smartor_xp@hotmail.com)
*
*/

define('IN_ICYPHOENIX', true);
if (!defined('IP_ROOT_PATH')) define('IP_ROOT_PATH', './');
if (!defined('PHP_EXT')) define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
include(IP_ROOT_PATH . 'common.' . PHP_EXT);
include(IP_ROOT_PATH . 'includes/functions_post.' . PHP_EXT);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();
// End session management

// Get general album information
$plugin_name = 'album';
if (empty($config['plugins'][$plugin_name]['enabled']))
{
	message_die(GENERAL_MESSAGE, 'PLUGIN_DISABLED');
}

$cms_page['page_id'] = 'album_comment_edit';
$cms_page['page_nav'] = (!empty($cms_config_layouts[$cms_page['page_id']]['page_nav']) ? true : false);
$cms_page['global_blocks'] = (!empty($cms_config_layouts[$cms_page['page_id']]['global_blocks']) ? true : false);
$cms_auth_level = (isset($cms_config_layouts[$cms_page['page_id']]['view']) ? $cms_config_layouts[$cms_page['page_id']]['view'] : AUTH_ALL);
check_page_auth($cms_page['page_id'], $cms_auth_level);
include(IP_ROOT_PATH . PLUGINS_PATH . $config['plugins'][$plugin_name]['dir'] . 'common.' . PHP_EXT);


include_once(IP_ROOT_PATH . 'includes/functions_users.' . PHP_EXT);
include_once(IP_ROOT_PATH . 'includes/bbcode.' . PHP_EXT);

$mode = request_var('mode', '');
if($mode == 'smilies')
{
	generate_smilies('window');
	exit;
}

// ------------------------------------
// Check feature enabled
// ------------------------------------

if( $album_config['comment'] == 0 )
{
	message_die(GENERAL_MESSAGE, $lang['Not_Authorized']);
}

// ------------------------------------
// Check the request
// ------------------------------------
$comment_id = request_var('comment_id', 0);
if(empty($comment_id))
{
	message_die(GENERAL_ERROR, 'No comment_id specified');
}
$message = request_var('message', '', true);
$comment_text = request_var('comment', '', true);
$message = (empty($message) ? $comment_text : $message);

// ------------------------------------
// Get the comment info
// ------------------------------------
$sql = "SELECT *
		FROM ". ALBUM_COMMENT_TABLE ."
		WHERE comment_id = '$comment_id'";
$result = $db->sql_query($sql);
$thiscomment = $db->sql_fetchrow($result);
if(empty($thiscomment))
{
	message_die(GENERAL_ERROR, 'This comment does not exist');
}

// ------------------------------------
// Get $pic_id from $comment_id
// ------------------------------------

$sql = "SELECT comment_id, comment_pic_id
		FROM ". ALBUM_COMMENT_TABLE ."
		WHERE comment_id = '$comment_id'";
$result = $db->sql_query($sql);
$row = $db->sql_fetchrow($result);
$pic_id = $row['comment_pic_id'];

// ------------------------------------
// Get this pic info and current category info
// ------------------------------------
// NOTE: we don't do a left join here against the category table
// since ALL pictures belong to some category, if not then it's database error
$sql = "SELECT p.*, cat.*, u.user_id, u.username, u.user_active, u.user_color, u.user_rank, COUNT(c.comment_id) as comments_count
		FROM ". ALBUM_CAT_TABLE ."  AS cat, ". ALBUM_TABLE ." AS p
			LEFT JOIN ". USERS_TABLE ." AS u ON p.pic_user_id = u.user_id
			LEFT JOIN ". ALBUM_COMMENT_TABLE ." AS c ON p.pic_id = c.comment_pic_id
		WHERE pic_id = '$pic_id'
			AND cat.cat_id = p.pic_cat_id
		GROUP BY p.pic_id
		LIMIT 1";
$result = $db->sql_query($sql);
$thispic = $db->sql_fetchrow($result);

$cat_id = ($thispic['pic_cat_id'] != 0) ? $thispic['pic_cat_id'] : $thispic['cat_id'];
//$user_id = $thispic['pic_user_id'];
$album_user_id = $thispic['cat_user_id'];

$total_comments = $thispic['comments_count'];
$comments_per_page = $config['posts_per_page'];

$pic_filename = $thispic['pic_filename'];
$pic_thumbnail = $thispic['pic_thumbnail'];

if(empty($thispic))
{
	message_die(GENERAL_ERROR, $lang['Pic_not_exist']);
}

// ------------------------------------
// Check the permissions
// ------------------------------------
$album_user_access = album_permissions($album_user_id, $cat_id, ALBUM_AUTH_COMMENT|ALBUM_AUTH_EDIT, $thispic);

if( ($album_user_access['comment'] == 0) || ($album_user_access['edit'] == 0) )
{
	if (!$user->data['session_logged_in'])
	{
		redirect(append_sid(CMS_PAGE_LOGIN . '?redirect=album_comment_edit.' . PHP_EXT . '?comment_id=' . $comment_id));
	}
	else
	{
		message_die(GENERAL_ERROR, $lang['Not_Authorized']);
	}
}
else
{
	if( (!$album_user_access['moderator']) && ($user->data['user_level'] != ADMIN) )
	{
		if ($thiscomment['comment_user_id'] != $user->data['user_id'])
		{
			message_die(GENERAL_ERROR, $lang['Not_Authorized']);
		}
	}
}

/*
+----------------------------------------------------------
| Main work here...
+----------------------------------------------------------
*/
album_read_tree($album_user_id);
$album_nav_cat_desc = album_make_nav_tree($cat_id, 'album_cat.' . PHP_EXT, 'nav' , $album_user_id);
if ($album_nav_cat_desc != '')
{
	$nav_server_url = create_server_url();
	$album_nav_cat_desc = ALBUM_NAV_ARROW . $album_nav_cat_desc;
	$breadcrumbs['address'] = ALBUM_NAV_ARROW . '<a href="' . $nav_server_url . append_sid('album.' . PHP_EXT) . '">' . $lang['Album'] . '</a>' . $album_nav_cat_desc;
}

$meta_content['page_title'] = $lang['Album'] . ' - ' . $thispic['pic_title'];
$meta_content['description'] = $lang['Album'] . ' - ' . strip_tags($thispic['cat_title']) . ' - ' . $thispic['pic_title'] . ' - ' . $thispic['pic_desc'];
$meta_content['keywords'] = $lang['Album'] . ', ' . strip_tags($thispic['cat_title']) . ', ' . $thispic['pic_title'] . ', ' . $thispic['pic_desc'] . ', ';

if(empty($message))
{

	$poster = ($thispic['username'] == '') ? $lang['Guest'] : colorize_username($thispic['user_id'], $thispic['username'], $thispic['user_color'], $thispic['user_active']);

	$template->assign_block_vars('switch_comment_post', array());

	$image_rating = ImageRating($thispic['rating']);

	//begin shows smilies
	$max_smilies = 20;

	$sql = 'SELECT emoticon, code, smile_url
		FROM ' . SMILIES_TABLE . '
			GROUP BY smile_url
			ORDER BY smilies_id LIMIT ' . $max_smilies;
	$result = $db->sql_query($sql);
	$smilies_count = $db->sql_numrows($result);
	$smilies_data = $db->sql_fetchrowset($result);

	for ($i = 1; $i < $smilies_count+1; $i++)
	{
		$template->assign_block_vars('switch_comment_post.smilies', array(
			'CODE' => $smilies_data[$i - 1]['code'],
			'URL' => $config['smilies_path'] . '/' . $smilies_data[$i - 1]['smile_url'],
			'DESC' => $smilies_data[$i - 1]['emoticon']
			)
		);

		if (is_integer($i / 5))
		{
			$template->assign_block_vars('switch_comment_post.smilies.new_col', array());
		}
	}

	// BBCBMG - BEGIN
	include(IP_ROOT_PATH . 'includes/bbcb_mg.' . PHP_EXT);
	$template->assign_var_from_handle('BBCB_MG', 'bbcb_mg');
	// BBCBMG - END
	// BBCBMG SMILEYS - BEGIN
	generate_smilies('inline');
	include(IP_ROOT_PATH . 'includes/bbcb_smileys_mg.' . PHP_EXT);
	$template->assign_var_from_handle('BBCB_SMILEYS_MG', 'bbcb_smileys_mg');
	// BBCBMG SMILEYS - END

	$pic_fullpath = ALBUM_UPLOAD_PATH . $thispic['pic_filename'];
	$pic_size = @getimagesize($pic_fullpath);
	$pic_width = $pic_size[0];
	$pic_height = $pic_size[1];
	$pic_filesize = @filesize($pic_fullpath);

	$template->assign_vars(array(
		'CAT_TITLE' => $thispic['cat_title'],
		'U_VIEW_CAT' => append_sid(album_append_uid('album_cat.' . PHP_EXT . '?cat_id=' . $cat_id)),

		'U_THUMBNAIL' => append_sid(album_append_uid('album_thumbnail.' . PHP_EXT . '?pic_id=' . $pic_id)),
		'U_PIC' => append_sid(album_append_uid('album_pic.' . PHP_EXT . '?pic_id=' . $pic_id)),

		'PIC_ID' => $pic_id,
		'PIC_TITLE' => $thispic['pic_title'],
		'PIC_DESC' => nl2br($thispic['pic_desc']),
		'POSTER' => $poster,
		'PIC_HEIGHT' => $pic_height,
		'PIC_WIDTH' => $pic_width,
		'PIC_TIME' => create_date($config['default_dateformat'], $thispic['pic_time'], $config['board_timezone']),
		'PIC_VIEW' => $thispic['pic_view_count'],
		'PIC_COMMENTS' => $total_comments,
		'S_MESSAGE' => $thiscomment['comment_text'],

		'L_PIC_ID' => $lang['Pic_ID'],
		'L_PIC_TITLE' => $lang['Pic_Image'],
		'L_PIC_DESC' => $lang['Pic_Desc'],
		'L_POSTER' => $lang['Pic_Poster'],
		'L_POSTED' => $lang['Posted'],
		'L_VIEW' => $lang['View'],
		'L_COMMENTS' => $lang['Comments'],

		'L_POST_YOUR_COMMENT' => $lang['Post_your_comment'],
		'L_MESSAGE' => $lang['Message'],
		'L_USERNAME' => $lang['Username'],
		'L_COMMENT_NO_TEXT' => $lang['Comment_no_text'],
		'L_COMMENT_TOO_LONG' => $lang['Comment_too_long'],
		'L_MAX_LENGTH' => $lang['Max_length'],
		'S_MAX_LENGTH' => $album_config['desc_length'],

		'L_SUBMIT' => $lang['Submit'],

		'S_ALBUM_ACTION' => append_sid(album_append_uid('album_comment_edit.' . PHP_EXT . '?comment_id=' . $comment_id))
		)
	);
	$template_to_parse = $class_plugins->get_tpl_file(ALBUM_TPL_PATH, 'album_comment_body.tpl');
	full_page_generation($template_to_parse, $meta_content['page_title'], $meta_content['description'], $meta_content['keywords']);
}
else
{
	// Comment Submited
	$comment_text = substr($message, 0, $album_config['desc_length']);
	if(empty($comment_text))
	{
		message_die(GENERAL_ERROR, $lang['Comment_no_text']);
	}

	// --------------------------------
	// Prepare variables
	// --------------------------------
	$comment_edit_time = time();
	$comment_edit_user_id = $user->data['user_id'];

	// --------------------------------
	// Update the DB
	// --------------------------------
	$sql = "UPDATE ". ALBUM_COMMENT_TABLE ."
			SET comment_text = '" . $db->sql_escape($comment_text) . "', comment_edit_time = '$comment_edit_time', comment_edit_count = comment_edit_count + 1, comment_edit_user_id = '$comment_edit_user_id'
			WHERE comment_id = '$comment_id'";
	$result = $db->sql_query($sql);

	// --------------------------------
	// Complete... now send a message to user
	// --------------------------------
	$return_url = 'album_showpage';

	$redirect_url = append_sid(album_append_uid($return_url . '.' . PHP_EXT . '?pic_id=' . $pic_id));
	meta_refresh(3, $redirect_url);

	$message = $lang['Stored'] . '<br /><br />' . sprintf($lang['Click_view_message'], '<a href="' . append_sid(album_append_uid($return_url . '.' . PHP_EXT . '?pic_id=' . $pic_id)) . '#c' . $comment_id . '">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_album_index'], '<a href="' . append_sid('album.' . PHP_EXT) . '">', '</a>');

	message_die(GENERAL_MESSAGE, $message);
}

?>