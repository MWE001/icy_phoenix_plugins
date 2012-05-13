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
* (c) 2005 oxpus (Karsten Ude) <webmaster@oxpus.de> http://www.oxpus.de
* (c) hotschi / demolition fabi / oxpus
*
*/

if (!defined('IN_ICYPHOENIX'))
{
	die('Hacking attempt');
	exit;
}

if (!$user->data['session_logged_in'])
{
	redirect(append_sid('downloads.' . PHP_EXT));
}

//
// Pull all user config data
//
if ($submit)
{
	$user_allow_new_download_popup = request_var('user_allow_new_download_popup', 0);
	$user_allow_fav_download_popup = request_var('user_allow_fav_download_popup', 0);
	$user_allow_new_download_email = request_var('user_allow_new_download_email', 0);
	$user_allow_fav_download_email = request_var('user_allow_fav_download_email', 0);
	$user_dl_note_type = request_var('user_dl_note_type', 0);
	$user_dl_sort_fix = request_var('user_dl_sort_fix', 0);
	$user_dl_sort_opt = request_var('user_dl_sort_opt', 0);
	$user_dl_sort_dir = request_var('user_dl_sort_dir', 0);

	$sql = "UPDATE " . USERS_TABLE . " SET
		user_allow_new_download_popup = $user_allow_new_download_popup,
		user_allow_fav_download_popup = $user_allow_fav_download_popup,
		user_allow_new_download_email = $user_allow_new_download_email,
		user_allow_fav_download_email = $user_allow_fav_download_email,
		user_dl_note_type = $user_dl_note_type,
		user_dl_sort_fix = $user_dl_sort_fix,
		user_dl_sort_opt = $user_dl_sort_opt,
		user_dl_sort_dir = $user_dl_sort_dir
		WHERE user_id = " . $user->data['user_id'];
	$db->sql_query($sql);

	if (defined('CASH_TABLE'))
	{
		$sql = "SELECT * FROM " . CASH_TABLE;
		$result = $db->sql_query($sql);

		while($row = $db->sql_fetchrow($result))
		{
			$cash_id = $row['cash_id'];
			$cash_dbfield = $row['cash_dbfield'];

			$cash_to_traffic = $row['cash_to_traffic'];
			$cash_to_traffic_in = request_var('cash_to_traffic_' . $cash_id, 0);
			$cash_to_traffic_in = ($cash_to_traffic_in > $user->data[$cash_dbfield]) ? $user->data[$cash_dbfield] : $cash_to_traffic_in;

			$add_traffic = $cash_to_traffic * $cash_to_traffic_in;

			$sql_user = "UPDATE " . USERS_TABLE . " SET
					$cash_dbfield = $cash_dbfield - $cash_to_traffic_in,
					user_traffic = user_traffic + $add_traffic
					WHERE user_id = " . $user->data['user_id'];

			$db->sql_query($sql_user);
		}
		$db->sql_freeresult($result);
	}

	$message = sprintf($lang['Dl_user_config_saved'], '<a href="' . append_sid('downloads.' . PHP_EXT) . '">', '</a>');

	message_die(GENERAL_MESSAGE, $message);
}

$allow_new_popup_yes = ($user->data['user_allow_new_download_popup']) ? 'checked="checked"' : '';
$allow_new_popup_no = (!$user->data['user_allow_new_download_popup']) ? 'checked="checked"' : '';
$allow_fav_popup_yes = ($user->data['user_allow_fav_download_popup']) ? 'checked="checked"' : '';
$allow_fav_popup_no = (!$user->data['user_allow_fav_download_popup']) ? 'checked="checked"' : '';
$allow_new_email_yes = ($user->data['user_allow_new_download_email']) ? 'checked="checked"' : '';
$allow_new_email_no = (!$user->data['user_allow_new_download_email']) ? 'checked="checked"' : '';
$allow_fav_email_yes = ($user->data['user_allow_fav_download_email']) ? 'checked="checked"' : '';
$allow_fav_email_no = (!$user->data['user_allow_fav_download_email']) ? 'checked="checked"' : '';

$user_dl_note_type_popup = ($user->data['user_dl_note_type']) ? 'checked="checked"' : '';
$user_dl_note_type_message = (!$user->data['user_dl_note_type']) ? 'checked="checked"' : '';
$user_dl_sort_opt = ($user->data['user_dl_sort_opt']) ? 'checked="checked"' : '';

$s_user_dl_sort_fix = '<select name="user_dl_sort_fix">';
$s_user_dl_sort_fix .= '<option value="0">' . $lang['Dl_default_sort'] . '</option>';
$s_user_dl_sort_fix .= '<option value="1">' . $lang['Dl_file_description'] . '</option>';
$s_user_dl_sort_fix .= '<option value="2">' . $lang['Dl_file_name'] . '</option>';
$s_user_dl_sort_fix .= '<option value="3">' . $lang['Dl_klicks'] . '</option>';
$s_user_dl_sort_fix .= '<option value="4">' . $lang['Dl_free'] . '</option>';
$s_user_dl_sort_fix .= '<option value="5">' . $lang['Dl_extern'] . '</option>';
$s_user_dl_sort_fix .= '<option value="6">' . $lang['Dl_file_size'] . '</option>';
$s_user_dl_sort_fix .= '<option value="7">' . $lang['Last_updated'] . '</option>';
$s_user_dl_sort_fix .= '<option value="8">' . $lang['Dl_rating'] . '</option>';
$s_user_dl_sort_fix .= '</select>';
$s_user_dl_sort_fix = str_replace('value="'.$user->data['user_dl_sort_fix'] . '">', 'value="'.$user->data['user_dl_sort_fix'] . '" selected="selected">', $s_user_dl_sort_fix);

$s_user_dl_sort_dir = '<select name="user_dl_sort_dir">';
$s_user_dl_sort_dir .= '<option value="0">' . $lang['Sort_Ascending'] . '</option>';
$s_user_dl_sort_dir .= '<option value="1">' . $lang['Sort_Descending'] . '</option>';
$s_user_dl_sort_dir .= '</select>';
$s_user_dl_sort_dir = str_replace('value="'.$user->data['user_dl_sort_dir'] . '">', 'value="'.$user->data['user_dl_sort_dir'] . '" selected="selected">', $s_user_dl_sort_dir);

/*
* display exchange settings for cash mod
*/
if (defined('CASH_TABLE'))
{
	$template->assign_block_vars('cash_title', array(
		'L_EXCHANGE_TITLE' => $lang['Dl_cash_to_traffic']
		)
	);

	$sql = "SELECT * FROM " . CASH_TABLE . "
		ORDER BY cash_name";
	$result = $db->sql_query($sql);

	while( $row = $db->sql_fetchrow($result) )
	{
		$cash_id = $row['cash_id'];
		$cash_name = $row['cash_name'];
		$cash_dbfield = $row['cash_dbfield'];
		$cash_to_traffic = $row['cash_to_traffic'];
		$cash_amount = sprintf($lang['Dl_cash_current_amount'], $user->data[$cash_dbfield], $cash_name);
		$cash_to_traffic_out = $dl_mod->dl_size($cash_to_traffic);
		$cash_exchange = sprintf($lang['Dl_cash_exchange'], $cash_name, $cash_to_traffic_out);

		$template->assign_block_vars('exchange_row', array(
			'CASH_ID' => $cash_id,
			'CASH_NAME' => $cash_name,
			'CASH_TO_TRAFFIC' => $cash_to_traffic_out,
			'CASH_EXCHANGE' => $cash_exchange,
			'CASH_AMOUNT' => $cash_amount)
		);
	}
}

/*
* drop all unaccessable favorites
*/
$access_cat = array();
$access_cat = $dl_mod->full_index(0, 0, 0, 1);
if (sizeof($access_cat))
{
	$sql_access_cat = implode(', ', $access_cat);

	$sql = "DELETE FROM " . DL_FAVORITES_TABLE . "
		WHERE fav_dl_cat NOT IN ($sql_access_cat)
			AND fav_user_id = " . $user->data['user_id'];
	$result = $db->sql_query($sql);
}

/*
* fetch all favorite downloads
*/
$sql = "SELECT f.fav_id, d.description, d.cat, d.id FROM " . DL_FAVORITES_TABLE . " f, " . DOWNLOADS_TABLE . " d
	WHERE f.fav_dl_id = d.id
		AND f.fav_user_id = " . $user->data['user_id'];
$result = $db->sql_query($sql);

$total_favorites = $db->sql_numrows($result);
if ($total_favorites)
{
	$template->assign_block_vars('fav_block', array(
		'L_DOWNLOAD' => $lang['Dl_favorite'],
		'L_DELETE' => $lang['Dl_delete'],
		'L_MARK_ALL' => $lang['Dl_mark_all'],
		'L_UNMARK_ALL' => $lang['Dl_unmark'],

		'S_FORM_ACTION' => append_sid('downloads.' . PHP_EXT . '?view=user_config')
		)
	);

	$row_class = '';
	$i = 0;
	while ($row = $db->sql_fetchrow($result))
	{
		$path_dl_array = array();
		$dl_nav = $dl_mod->dl_nav($row['cat'], 'url').'&nbsp;&raquo;&nbsp;';

		$row_class = ip_zebra_rows($row_class);
		$template->assign_block_vars('fav_block.favorite_row', array(
			'ROW_CLASS' => $row_class,
			'DL_ID' => $row['fav_id'],
			'DL_CAT' => $dl_nav,
			'DOWNLOAD' => $row['description'],
			'U_DOWNLOAD' => append_sid('downloads.' . PHP_EXT . '?view=detail&amp;df_id='.$row['id'] . '&amp;cat_id=' . $row['cat'])
			)
		);
		$i++;
	}
}
$db->sql_freeresult($result);

$template_to_parse = $class_plugins->get_tpl_file(DL_TPL_PATH, 'dl_user_config_body.tpl');

if (!$dl_config['sort_preform'])
{
	$template->assign_block_vars('sort_config_options', array(
		'L_DL_SORT_USER_OPT' => $lang['Dl_sort_user_opt'],
		'L_DL_SORT_USER_EXT' => $lang['Dl_sort_user_ext'],

		'S_DL_SORT_USER_OPT' => $s_user_dl_sort_fix,
		'S_DL_SORT_USER_EXT' => $user_dl_sort_opt,
		'S_DL_SORT_USER_DIR' => $s_user_dl_sort_dir)
	);
}

$s_hidden_fields = '<input type="hidden" name="'.POST_USERS_URL.'" value="'.$user->data['user_id'] . '" />';

$template->assign_vars(array(
	'L_CONFIGURATION_TITLE' => $meta_content['page_title'],
	'L_DL_TRAFFIC' => $lang['Traffic'],
	'L_DOWNLOADS' => $lang['Dl_cat_title'],

	'L_YES' => $lang['Yes'],
	'L_NO' => $lang['No'],

	'L_SUBMIT' => $lang['Submit'],
	'L_RESET' => $lang['Reset'],

	'L_DOWNLOAD_POPUP' => $lang['User_download_popup'],
	'L_DOWNLOAD_EMAIL' => $lang['User_download_email'],
	'L_DOWNLOAD_NOTIFY_TYPE' => $lang['User_download_notify_type'],
	'L_DOWNLOAD_NOTIFY_TYPE_POPUP' => $lang['User_download_notify_type_popup'],
	'L_DOWNLOAD_NOTIFY_TYPE_MESSAGE' => $lang['User_download_notify_type_message'],

	'L_ALLOW_NEW_DOWNLOAD_POPUP' => $lang['User_allow_new_download_popup'],
	'L_ALLOW_FAV_DOWNLOAD_POPUP' => $lang['User_allow_fav_download_popup'],
	'L_ALLOW_NEW_DOWNLOAD_EMAIL' => $lang['User_allow_new_download_email'],
	'L_ALLOW_FAV_DOWNLOAD_EMAIL' => $lang['User_allow_fav_download_email'],

	'ALLOW_NEW_DOWNLOAD_POPUP_YES' => $allow_new_popup_yes,
	'ALLOW_NEW_DOWNLOAD_POPUP_NO' => $allow_new_popup_no,
	'ALLOW_FAV_DOWNLOAD_POPUP_YES' => $allow_fav_popup_yes,
	'ALLOW_FAV_DOWNLOAD_POPUP_NO' => $allow_fav_popup_no,

	'ALLOW_NEW_DOWNLOAD_EMAIL_YES' => $allow_new_email_yes,
	'ALLOW_NEW_DOWNLOAD_EMAIL_NO' => $allow_new_email_no,
	'ALLOW_FAV_DOWNLOAD_EMAIL_YES' => $allow_fav_email_yes,
	'ALLOW_FAV_DOWNLOAD_EMAIL_NO' => $allow_fav_email_no,

	'USER_DL_NOTE_TYPE_POPUP' => $user_dl_note_type_popup,
	'USER_DL_NOTE_TYPE_MESSAGE' => $user_dl_note_type_message,

	'S_HIDDEN_FIELDS' => $s_hidden_fields,
	'S_CONFIG_ACTION' => append_sid('downloads.' . PHP_EXT . '?view=user_config'),

	'U_DOWNLOADS_ADV' => append_sid('downloads.' . PHP_EXT)
	)
);

if ($dl_config['disable_email'] == 0)
{
	$template->assign_block_vars('no_dl_email_notify', array());
}

if ($dl_config['disable_popup'] == 0)
{
	$template->assign_block_vars('no_dl_popup_notify', array());
}

?>