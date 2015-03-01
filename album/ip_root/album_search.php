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
* Volodymyr (CLowN) Skoryk (blaatimmy72@yahoo.com)
*
*/

define('IN_ICYPHOENIX', true);
if (!defined('IP_ROOT_PATH')) define('IP_ROOT_PATH', './');
if (!defined('PHP_EXT')) define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
include(IP_ROOT_PATH . 'common.' . PHP_EXT);

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

$cms_page['page_id'] = 'album_search';
$cms_page['page_nav'] = (!empty($cms_config_layouts[$cms_page['page_id']]['page_nav']) ? true : false);
$cms_page['global_blocks'] = (!empty($cms_config_layouts[$cms_page['page_id']]['global_blocks']) ? true : false);
$cms_auth_level = (isset($cms_config_layouts[$cms_page['page_id']]['view']) ? $cms_config_layouts[$cms_page['page_id']]['view'] : AUTH_ALL);
check_page_auth($cms_page['page_id'], $cms_auth_level);
include(IP_ROOT_PATH . PLUGINS_PATH . $config['plugins'][$plugin_name]['dir'] . 'common.' . PHP_EXT);

$nav_server_url = create_server_url();
$album_nav_cat_desc = ALBUM_NAV_ARROW . '<a href="' . $nav_server_url . append_sid('album_search.' . PHP_EXT) . '" class="nav-current">' . $lang['Search'] . '</a>';
$breadcrumbs['address'] = ALBUM_NAV_ARROW . '<a href="' . $nav_server_url . append_sid('album.' . PHP_EXT) . '">' . $lang['Album'] . '</a>' . $album_nav_cat_desc;

$mode = request_var('mode', '', true);
$search = request_var('search', '', true);
$search_escaped = $db->sql_escape(strtolower($search));

if (!empty($search_escaped))
{
	$template->assign_block_vars('switch_search_results', array());

	if (empty($mode))
	{
		message_die(GENERAL_ERROR, 'Bad request');
	}

	if ($mode == 'user')
	{
		$where = "AND LOWER(p.pic_username) LIKE '%" . $search_escaped . "%'";
	}
	elseif ($mode == 'name')
	{
		$where = "AND LOWER(p.pic_title) LIKE '%" . $search_escaped . "%'";
	}
	elseif ($mode == 'desc')
	{
		$where = "AND LOWER(p.pic_desc) LIKE '%" . $search_escaped . "%'";
	}
	elseif ($mode == 'name_desc')
	{
		$where = "AND (LOWER(p.pic_desc) LIKE '%" . $search_escaped . "%' OR LOWER(p.pic_title) LIKE '%" . $search_escaped . "%')";
	}
	else
	{
		message_die(GENERAL_ERROR, 'Bad request');
	}

	// Add category filtering (taken from album_allpics.php)
	$album_user_id = ALBUM_PUBLIC_GALLERY;
	$catrows = array ();
	$options = ALBUM_READ_ALL_CATEGORIES | ALBUM_AUTH_VIEW;
	$catrows = album_read_tree($album_user_id, $options);
	$allowed_cats = '';
	for ($i = 0; $i < sizeof($catrows); $i++)
	{
		$allowed_cats .= ($allowed_cats == '') ? $catrows[$i]['cat_id'] : ',' . $catrows[$i]['cat_id'];
	}
	if ($allowed_cats != '')
	{
		$where .= ' AND c.cat_id IN (' . $allowed_cats . ')';
	}

	// --------------------------------
	// Pagination
	// --------------------------------

	// Number of matches displayed
	$pics_per_page = $album_config['rows_per_page'] * $album_config['cols_per_page'];
	if ($pics_per_page == 0)
	{
		$pics_per_page = 20;
	}
	//$pics_per_page = 4;

	$start = request_var('start', 0);
	$start = ($start < 0) ? 0 : $start;

	// ------------------------------------
	// Count pic matches
	// ------------------------------------

	if (($album_config['personal_gallery_view'] == -1) || ($user->data['user_level'] == ADMIN))
	{
		$search_pg = '';
	}
	else
	{
		$search_pg = 'AND c.cat_user_id = 0';
	}
	$limit_sql = ($start == 0) ? $pics_per_page : $start . ',' . $pics_per_page;

	$count_sql = "SELECT COUNT(pic_id) AS count
								FROM " . ALBUM_TABLE . ' AS p,' . ALBUM_CAT_TABLE . " AS c
								WHERE p.pic_approval = 1
								AND p.pic_cat_id = c.cat_id
								" . $where . "
								" . $search_pg;
	$result = $db->sql_query($count_sql);
	$row = $db->sql_fetchrow($result);
	$total_pics = $row['count'];

	$sql = "SELECT p.pic_id, p.pic_title, p.pic_desc, p.pic_user_id, p.pic_username, p.pic_time, p.pic_cat_id, p.pic_approval, c.cat_id, c.cat_title, c.cat_user_id
					FROM " . ALBUM_TABLE . ' AS p,' . ALBUM_CAT_TABLE . " AS c
					WHERE p.pic_approval = 1
						AND p.pic_cat_id = c.cat_id
						" . $where . "
						" . $search_pg . "
					ORDER BY p.pic_time DESC LIMIT ".$limit_sql."";
	$result = $db->sql_query($sql);

	$numres = 0;

	if ($row = $db->sql_fetchrow($result))
	{
		$in = array();
		do
		{
			if (!in_array($row['pic_id'], $in))
			{
				$album_user_id = $row['cat_user_id'];
				$cat_id = $row['cat_id'];
				//$cat_id = album_get_personal_root_id($album_user_id);

				$check_permissions = ALBUM_AUTH_VIEW|ALBUM_AUTH_RATE|ALBUM_AUTH_COMMENT|ALBUM_AUTH_EDIT|ALBUM_AUTH_DELETE;
				$auth_data = album_permissions($album_user_id, $cat_id, $check_permissions, $row);
				//$auth_data = album_get_auth_data($cat_id);

				$pic_preview = '';
				$pic_preview_hs = '';
				if ($album_config['lb_preview'])
				{
					$slideshow_cat = '';
					$slideshow = !empty($slideshow_cat) ? ', { slideshowGroup: \'' . $slideshow_cat . '\' } ' : '';
					$pic_preview_hs = ' class="highslide" onclick="return hs.expand(this' . $slideshow . ');"';

					$pic_preview = 'onmouseover="showtrail(\'' . append_sid(album_append_uid('album_picm.' . PHP_EXT . '?pic_id=' . $row['pic_id'])) . '\',\'' . addslashes($row[$j]['pic_title']) . '\', ' . $album_config['midthumb_width'] . ', ' . $album_config['midthumb_height'] . ')" onmouseout="hidetrail()"';
				}

				//if(!$auth_data['view'])
				if ($auth_data['view'] >= 0)
				{
					$template_vars = array(
						'L_USERNAME' => $row['pic_username'],
						'U_PROFILE' => append_sid(CMS_PAGE_PROFILE . '?mode=viewprofile&u=' . $row['pic_user_id']),
						'PIC_PREVIEW_HS' => $pic_preview_hs,
						'PIC_PREVIEW' => $pic_preview,
						'CATEGORY' => ($row['cat_user_id'] != ALBUM_PUBLIC_GALLERY) ? $lang['Users_Personal_Galleries'] : $row['cat_title'],
						'U_PIC_CAT' => ($row['cat_id'] == $cat_id) ? append_sid(album_append_uid('album_cat.' . PHP_EXT . '?cat_id=' . $row['cat_id'])) : append_sid(album_append_uid('album.' . PHP_EXT)),
						'GROUP_NAME' => 'all',
					);
					album_build_detail_vars($template_vars, $row);
					$template->assign_block_vars('switch_search_results.search_results', $template_vars);

					$in[$numres] = $row['pic_id'];
					$numres++;
				}
			}
		}
		while($row = $db->sql_fetchrow($result));

		$template->assign_vars(array(
			'L_NRESULTS' => $numres,
			'L_TRESULTS' => $total_pics,
			'IMG_FOLDER' => $images['topic_nor_read'],
			'L_TCATEGORY' => $lang['Pic_Cat'],
			'L_TTITLE' => $lang['Pic_Image'],
			'L_TSUBMITER' => $lang['Author'],
			'L_TSUBMITED' => $lang['Time'],
			'S_THUMBNAIL_SIZE' => $album_config['thumbnail_size'],
			)
		);
	}
	else
	{
		message_die(GENERAL_MESSAGE, $lang['No_search_match']);
	}
}
else
{
	message_die(GENERAL_ERROR, 'Bad request');
	//$template->assign_block_vars('switch_search', array());
}

// --------------------------------
// Pagination
// --------------------------------

$template->assign_vars(array(
	'PAGINATION' => generate_pagination(append_sid(album_append_uid('album_search.' . PHP_EXT . '?mode=' . $mode . '&amp;search=' . $search)), $total_pics, $pics_per_page, $start),
	'PAGE_NUMBER' => sprintf($lang['Page_of'], (floor($start / $pics_per_page) + 1), ceil($total_pics / $pics_per_page))
	)
);

$template_to_parse = $class_plugins->get_tpl_file(ALBUM_TPL_PATH, 'album_search_body.tpl');
full_page_generation($template_to_parse, $lang['Search'], '', '');

?>