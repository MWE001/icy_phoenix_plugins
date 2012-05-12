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
* MX-System - (jonohlsson@hotmail.com) - (www.mx-system.com)
*
*/

define('IN_ICYPHOENIX', true);

if (!empty($setmodules))
{
	if (empty($config['plugins']['kb']['enabled']))
	{
		return;
	}

	$file = IP_ROOT_PATH . PLUGINS_PATH . $config['plugins']['kb']['dir'] . ADM . '/' . basename(__FILE__);
	$module['1800_KB_title']['130_Types_man'] = $file;
	return;
}

if (!defined('IP_ROOT_PATH')) define('IP_ROOT_PATH', './../../../');
if (!defined('PHP_EXT')) define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
require(IP_ROOT_PATH . 'adm/pagestart.' . PHP_EXT);

include(IP_ROOT_PATH . PLUGINS_PATH . $config['plugins']['kb']['dir'] . 'common.' . PHP_EXT);

if(!function_exists('get_list_kb'))
{
	function get_list_kb($id, $select)
	{
		global $db;

		$idfield = 'id';
		$namefield = 'type';

		$sql = "SELECT *
			FROM " . KB_TYPES_TABLE;

		if ($select == 0)
		{
			$sql .= " WHERE $idfield <> $id";
		}

		$result = $db->sql_query($sql);

		$typelist = '';

		while ($row = $db->sql_fetchrow($result))
		{
			$typelist .= "<option value=\"$row[$idfield]\"$s>" . $row[$namefield] . "</option>\n";
		}

		return($typelist);
	}
}

// Load default header
$mode = request_var('mode', '');
if (empty($mode))
{
	$mode = '';
	if ($create)
	{
		$mode = 'create';
	}
	else if ($edit)
	{
		$mode = 'edit';
	}
	else if ($delete)
	{
		$mode = 'delete';
	}
}

switch ($mode)
{
	case 'create':
		$type_name = request_var('new_type_name', '', true);

		if (!$type_name)
		{
			echo "Please put a type name in!";
			exit;
		}

		$sql = "INSERT INTO " . KB_TYPES_TABLE . " (type) VALUES ('" . $db->sql_escape($type_name) . "')";
		$result = $db->sql_query($sql);

		$message = $lang['Type_created'] . '<br /><br />' . sprintf($lang['Click_return_type_manager'], '<a href="' . append_sid('admin_kb_types.' . PHP_EXT) . '">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_admin_index'], '<a href="' . append_sid(IP_ROOT_PATH . ADM . '/index.' . PHP_EXT . '?pane=right') . '">', '</a>');

		mx_message_die(GENERAL_MESSAGE, $message);
		break;

	case 'edit':

		if (!$_POST['submit'])
		{
			$type_id = intval($_GET['cat']);

			$sql = "SELECT * FROM " . KB_TYPES_TABLE . " WHERE id = " . $type_id;
			$result = $db->sql_query($sql);
			if ($type = $db->sql_fetchrow($result))
			{
				$type = $type['type'];
			}

			// Generate page

			$template->set_filenames(array('body' => KB_ADM_TPL_PATH . 'kb_type_edit_body.tpl'));

			$template->assign_vars(array('L_EDIT_TITLE' => $lang['Edit_type'],
				'L_CATEGORY' => $lang['Article_type'],
				'L_CAT_SETTINGS' => $lang['Cat_settings'],
				'L_CREATE' => $lang['Edit'],

				'S_ACTION' => append_sid('admin_kb_types.' . PHP_EXT . '?mode=edit'),
				'CAT_NAME' => $type,

				'S_HIDDEN' => '<input type="hidden" name="typeid" value="' . $type_id . '">'
				)
			);
		}
		elseif ($_POST['submit'])
		{
			$type_id = request_var('typeid', 0);
			$type_name = request_var('catname', '', true);

			if (!$type_name)
			{
				echo "Please put a type name in!";
				exit;
			}

			$sql = "UPDATE " . KB_TYPES_TABLE . " SET type = '" . $db->sql_escape($type_name) . "' WHERE id = " . $type_id;
			$result = $db->sql_query($sql);

			$message = $lang['Type_edited'] . '<br /><br />' . sprintf($lang['Click_return_type_manager'], '<a href="' . append_sid('admin_kb_types.' . PHP_EXT) . '">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_admin_index'], '<a href="' . append_sid(IP_ROOT_PATH . ADM . '/index.' . PHP_EXT . '?pane=right') . '">', '</a>');

			mx_message_die(GENERAL_MESSAGE, $message);
		}
		break;

	case 'delete':

		if (!$_POST['submit'])
		{
			$type_id = request_var('cat', 0);

			$sql = "SELECT * FROM " . KB_TYPES_TABLE . " WHERE id = '" . $type_id . "'";
			$cat_result = $db->sql_query($sql);

			if ($type = $db->sql_fetchrow($cat_result))
			{
				$type_name = $type['type'];
			}

			// Generate page

			$template->set_filenames(array('body' => KB_ADM_TPL_PATH . 'kb_cat_del_body.tpl'));

			$template->assign_vars(array(
				'L_DELETE_TITLE' => $lang['Type_delete_title'],
				'L_DELETE_DESCRIPTION' => $lang['Type_delete_desc'],
				'L_CAT_DELETE' => $lang['Type_delete_title'],

				'L_CAT_NAME' => $lang['Article_type'],
				'L_MOVE_CONTENTS' => $lang['Change_type'],
				'L_DELETE' => $lang['Change_and_Delete'],

				'S_HIDDEN_FIELDS' => '<input type="hidden" name="typeid" value="' . $type_id . '">',
				'S_SELECT_TO' => get_list_kb($type_id, 0),
				'S_ACTION' => append_sid('admin_kb_types.' . PHP_EXT . '?mode=delete'),

				'CAT_NAME' => $type_name
				)
			);
		}
		elseif ($_POST['submit'])
		{
			$new_type = request_var('move_id', '');
			$old_type = request_var('typeid', '');

			if ($new_type)
			{
				$sql = "UPDATE " . KB_ARTICLES_TABLE . " SET article_type = '" . $db->sql_escape($new_type) . "' WHERE article_type = '" . $db->sql_escape($old_type) . "'";
				$move_result = $db->sql_query($sql);
			}
			$sql = "DELETE FROM " . KB_TYPES_TABLE . " WHERE id = '" . $db->sql_escape($old_type) . "'";
			$delete_result = $db->sql_query($sql);

			$message = $lang['Type_deleted'] . '<br /><br />' . sprintf($lang['Click_return_type_manager'], '<a href="' . append_sid('admin_kb_types.' . PHP_EXT) . '">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_admin_index'], '<a href="' . append_sid(IP_ROOT_PATH . ADM . '/index.' . PHP_EXT . '?pane=right') . '">', '</a>');

			mx_message_die(GENERAL_MESSAGE, $message);
		}
		break;

	default:

		// Generate page
		$template->set_filenames(array('body' => KB_ADM_TPL_PATH . 'kb_type_body.tpl'));

		$template->assign_vars(array('L_KB_TYPE_TITLE' => $lang['Types_man'],
			'L_KB_TYPE_DESCRIPTION' => $lang['KB_types_description'],

			'L_CREATE_TYPE' => $lang['Create_type'],
			'L_CREATE' => $lang['Create'],
			'L_TYPE' => $lang['Article_type'],
			'L_ACTION' => $lang['Art_action'],

			'S_ACTION' => append_sid('admin_kb_types.' . PHP_EXT . '?mode=create')
			)
		);
		// get categories
		$sql = "SELECT * FROM " . KB_TYPES_TABLE;
		$cat_result = $db->sql_query($sql);

		while ($type = $db->sql_fetchrow($cat_result))
		{
			$type_id = $type['id'];
			$type_name = $type['type'];

			$temp_url = append_sid(KB_ADM_PATH . 'admin_kb_types.' . PHP_EXT . '?mode=edit&amp;cat=' . $type_id);
			//$edit = '<a href="' . $temp_url . '"><img src="' . $images['icon_edit'] . '" alt="' . $lang['Edit'] . '"></a>';
			$edit = '<a href="' . $temp_url . '">' . $lang['Edit'] . '</a>';

			$temp_url = append_sid(KB_ADM_PATH . 'admin_kb_types.' . PHP_EXT . '?mode=delete&amp;cat=' . $type_id);
			//$delete = '<a href="' . $temp_url . '"><img src="' . $images['icon_delpost'] . '" alt="' . $lang['Delete'] . '"></a>';
			$delete = '<a href="' . $temp_url . '">' . $lang['Delete'] . '</a>';

			$row_class = (!($i % 2)) ? $theme['td_class1'] : $theme['td_class2'];

			$template->assign_block_vars('typerow', array(
				'TYPE' => $type_name,
				'U_EDIT' => $edit,
				'U_DELETE' => $delete,
				'ROW_CLASS' => $row_class
				)
			);
			$i++;
		}
		break;
}

$template->pparse('body');

include(IP_ROOT_PATH . ADM . '/page_footer_admin.' . PHP_EXT);

?>