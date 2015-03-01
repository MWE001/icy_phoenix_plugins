<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
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

$cms_page['page_id'] = 'album_allpics';
$cms_page['page_nav'] = (!empty($cms_config_layouts[$cms_page['page_id']]['page_nav']) ? true : false);
$cms_page['global_blocks'] = (!empty($cms_config_layouts[$cms_page['page_id']]['global_blocks']) ? true : false);
$cms_auth_level = (isset($cms_config_layouts[$cms_page['page_id']]['view']) ? $cms_config_layouts[$cms_page['page_id']]['view'] : AUTH_ALL);
check_page_auth($cms_page['page_id'], $cms_auth_level);
include(IP_ROOT_PATH . PLUGINS_PATH . $config['plugins'][$plugin_name]['dir'] . 'common.' . PHP_EXT);


$mode = request_var('mode', '');
$album_view_mode = strtolower($mode);

// make sure that it only contains some valid value
switch ($album_view_mode)
{
	case ALBUM_VIEW_ALL:
		$album_view_mode = ALBUM_VIEW_ALL;
		break;
	case ALBUM_VIEW_ALL_PICS:
		$album_view_mode = ALBUM_VIEW_ALL_PICS;
		break;
	case ALBUM_VIEW_LIST:
		$album_view_mode = ALBUM_VIEW_LIST;
		break;
	default:
		//$album_view_mode = '';
		$album_view_mode = ALBUM_VIEW_LIST;
}

//$album_user_id = $user->data['user_id'];
$album_user_id = ALBUM_PUBLIC_GALLERY;
//$album_user_id = ALBUM_ROOT_CATEGORY;
$catrows = array ();
$options = ($album_view_mode == ALBUM_VIEW_LIST) ? (ALBUM_READ_ALL_CATEGORIES | ALBUM_AUTH_VIEW) : ALBUM_AUTH_VIEW;
$catrows = album_read_tree($album_user_id, $options);

album_read_tree($album_user_id);
$allowed_cat = ''; // For Recent Public Pics below
for ($i = 0; $i < sizeof($catrows); $i++)
{
	$allowed_cat .= ($allowed_cat == '') ? $catrows[$i]['cat_id'] : ',' . $catrows[$i]['cat_id'];
}

if ($allowed_cat == '')
{
	message_die(GENERAL_ERROR, $lang['No_Pics']);
}

// ------------------------------------
// Build the sort method and sort order information
// ------------------------------------
$start = request_var('start', 0);
$start = ($start < 0) ? 0 : $start;

$type = request_var('type', '');
$album_view_type = strtolower($type);

$sort_method_default = $album_config['sort_method'];
switch ($album_view_type)
{
	case ALBUM_LISTTYPE_RATINGS:
		$sort_method_default = 'rating';
		break;
	case ALBUM_LISTTYPE_COMMENTS:
		$sort_method_default = 'comments';
		break;
}

$sort_method = request_var('sort_method', $sort_method_default);
$sort_method = check_var_value($sort_method, array('pic_id', 'pic_time', 'pic_title', 'username', 'pic_view_count', 'rating', 'comments', 'new_comment'));

$sort_order = request_var('sort_order', $album_config['sort_order']);
$sort_order = check_var_value($sort_order, array('DESC', 'ASC'));

$sort_append = '&amp;sort_method=' . $sort_method . '&amp;sort_order=' . $sort_order;

switch ($sort_method)
{
	case 'pic_time':
		$sort_method_sql = 'p.pic_time';
		break;
	case 'pic_title':
		$sort_method_sql = 'p.pic_title';
		break;
	case 'username':
		$sort_method_sql = 'u.username';
		break;
	case 'pic_view_count':
		$sort_method_sql = 'p.pic_view_count';
		break;
	case 'rating':
		$sort_method_sql = 'rating';
		break;
	case 'comments':
		$sort_method_sql = 'comments';
		break;
	case 'new_comment':
		$sort_method_sql = 'comments';
		//$sort_method_sql = 'new_comment';
		break;
	default:
		$sort_method_sql = 'p.pic_id';
}

// ------------------------------------
// additional sorting options
// ------------------------------------
$sort_rating_option = '';
$sort_comments_option = '';
$sort_new_comment_option = '';

if ($album_config['rate'] == 1)
{
	$sort_rating_option = '<option value="rating" ';
	$sort_rating_option .= ($sort_method == 'rating') ? 'selected="selected"' : '';
	$sort_rating_option .= '>' . $lang['Rating'] . '</option>';
}
if ($album_config['comment'] == 1)
{
	$sort_comments_option = '<option value="comments" ';
	$sort_comments_option .= ($sort_method == 'comments') ? 'selected="selected"' : '';
	$sort_comments_option .= '>' . $lang['Comments'] . '</option>';

	$sort_new_comment_option = '<option value="new_comment" ';
	$sort_new_comment_option .= ($sort_method == 'new_comment') ? 'selected="selected"' : '';
	$sort_new_comment_option .= '>' . $lang['New_Comment'] . '</option>';
}

/*
+----------------------------------------------------------
| Start output the page
+----------------------------------------------------------
*/

$meta_content['page_title'] = $lang['Album'];
$meta_content['description'] = '';
$meta_content['keywords'] = '';

// ------------------------------------
// Build the thumbnail page
// ------------------------------------

/*
if ($album_view_mode == ALBUM_VIEW_ALL_PICS)
{
	include(ALBUM_MOD_PATH . 'album_allpics.' . PHP_EXT);
}
*/

$pics_per_page = $album_config['rows_per_page'] * $album_config['cols_per_page'];
$limit_sql = ($start == 0) ? $pics_per_page : $start .','. $pics_per_page;

// set some initial values...
// $allowed_cat is set in album.php !!!
$list_sql = '';
$count_sql = '';
//$album_view_type = ALBUM_LISTTYPE_PICTURES;
switch ($album_view_type)
{
	case ALBUM_LISTTYPE_RATINGS:
		$album_view_type = ALBUM_LISTTYPE_RATINGS;

		// default sorting if not specified directly
		if (!isset($_GET['sort_method']) && !isset($_POST['sort_method']))
		{
			$sort_method = 'rating';
			$sort_method_sql = 'rating';
			$sort_order = 'ASC';
		}

		$count_sql = 'SELECT COUNT(rate_pic_id) AS count
						FROM '. ALBUM_RATE_TABLE .', '. ALBUM_TABLE .', '.ALBUM_CAT_TABLE .'
						WHERE cat_id IN (' . $allowed_cat .')
							AND pic_id = rate_pic_id
							AND pic_cat_id = cat_id';

		$list_sql = "SELECT DISTINCT(p.pic_id), ct.cat_user_id, ct.cat_id, ct.cat_title, p.pic_title, p.pic_desc, p.pic_user_id, p.pic_user_ip, p.pic_time, p.pic_view_count, p.pic_lock, p.pic_filename, p.pic_thumbnail, r.rate_pic_id, r.rate_pic_id,
						AVG(r.rate_point) AS rating, COUNT(DISTINCT c.comment_id) AS comments, MAX(c.comment_id) as new_comment
					FROM ".ALBUM_RATE_TABLE." AS r
						LEFT JOIN ".ALBUM_TABLE. " AS p ON p.pic_id = r.rate_pic_id
						LEFT JOIN ".ALBUM_COMMENT_TABLE." AS c ON p.pic_id = c.comment_pic_id
						LEFT JOIN ".ALBUM_CAT_TABLE." AS ct ON p.pic_cat_id = ct.cat_id
					WHERE ct.cat_id IN ($allowed_cat)
					GROUP BY r.rate_pic_id
					ORDER BY $sort_method_sql $sort_order
					LIMIT $limit_sql";
		break;
	case ALBUM_LISTTYPE_COMMENTS:
		$album_view_type = ALBUM_LISTTYPE_COMMENTS;

		// default sorting if not specified directly
		if (!isset($_GET['sort_method']) && !isset($_POST['sort_method']))
		{
			$sort_method = 'comments';
			$sort_method_sql = 'comments';
			$sort_order = 'ASC';
		}

		$count_sql = 'SELECT COUNT(comment_id) AS count
						FROM '. ALBUM_COMMENT_TABLE .', '. ALBUM_CAT_TABLE .'
						WHERE cat_id IN (' . $allowed_cat .')
							AND comment_cat_id = cat_id';

		$list_sql = "SELECT DISTINCT(p.pic_id), ct.cat_user_id, ct.cat_id, ct.cat_title, p.pic_title, p.pic_desc, p.pic_user_id, p.pic_user_ip, p.pic_time, p.pic_view_count, p.pic_lock, p.pic_filename, p.pic_thumbnail, r.rate_pic_id,
						AVG(r.rate_point) AS rating, COUNT(DISTINCT c.comment_id) AS comments, MAX(c.comment_id) as new_comment, c.comment_pic_id
					FROM ".ALBUM_COMMENT_TABLE." AS c
						LEFT JOIN ".ALBUM_TABLE. " AS p ON c.comment_pic_id = p.pic_id
						LEFT JOIN ".ALBUM_RATE_TABLE." AS r ON p.pic_id = r.rate_pic_id
						LEFT JOIN ".ALBUM_CAT_TABLE." AS ct ON p.pic_cat_id = ct.cat_id
					WHERE ct.cat_id IN ($allowed_cat)
					GROUP BY c.comment_pic_id
					ORDER BY $sort_method_sql $sort_order
					LIMIT $limit_sql";
		break;
	default:
		$album_view_type = ALBUM_LISTTYPE_PICTURES;

		$count_sql = 'SELECT COUNT(pic_id) AS count
						FROM '. ALBUM_TABLE .', '. ALBUM_CAT_TABLE .'
						WHERE cat_id IN (' . $allowed_cat . ')
							AND pic_cat_id = cat_id';

		$list_sql = "SELECT DISTINCT(p.pic_id), ct.cat_user_id, ct.cat_id, ct.cat_title, p.pic_title, p.pic_desc, p.pic_user_id, p.pic_user_ip, p.pic_time, p.pic_view_count, p.pic_lock, p.pic_filename, p.pic_thumbnail, r.rate_pic_id,
						AVG(r.rate_point) AS rating, COUNT(DISTINCT c.comment_id) AS comments, MAX(c.comment_id) as new_comment
					FROM " . ALBUM_TABLE . " AS p
						LEFT JOIN " . ALBUM_RATE_TABLE . " AS r ON p.pic_id = r.rate_pic_id
						LEFT JOIN " . ALBUM_COMMENT_TABLE . " AS c ON p.pic_id = c.comment_pic_id
						LEFT JOIN " . ALBUM_CAT_TABLE . " AS ct ON p.pic_cat_id = ct.cat_id
					WHERE ct.cat_id IN ($allowed_cat)
					GROUP BY p.pic_id
					ORDER BY $sort_method_sql $sort_order
					LIMIT $limit_sql";
}

// ------------------------------------
// Count pics, comments or ratings
// ------------------------------------

$result = $db->sql_query($count_sql);
$row = $db->sql_fetchrow($result);
$total_pics = $row['count'];

// ------------------------------------
// Build up
// ------------------------------------

$album_view_mode_param = (!empty($album_view_mode)) ? '&amp;mode=' . $album_view_mode : '';
$album_view_type_param = (!empty($album_view_type)) ? '&amp;type=' . $album_view_type : '';

if ($total_pics > 0 && !empty($allowed_cat))
{
	$result = $db->sql_query($list_sql);
	$picrow = array();
	while($row = $db->sql_fetchrow($result))
	{
		$picrow[] = $row;
	}

	// --------------------------------
	// Thumbnails table
	// --------------------------------

	for ($i = 0; $i < sizeof($picrow); $i += $album_config['cols_per_page'])
	{
		$template->assign_block_vars('picrow', array());

		for ($j = $i; $j < ($i + $album_config['cols_per_page']); $j++)
		{
			if($j >= sizeof($picrow))
			{
				break;
			}
			$pic_preview = '';
			$pic_preview_hs = '';
			if ($album_config['lb_preview'])
			{
				$slideshow_cat = '';
				$slideshow = !empty($slideshow_cat) ? ', { slideshowGroup: \'' . $slideshow_cat . '\' } ' : '';
				$pic_preview_hs = ' class="highslide" onclick="return hs.expand(this' . $slideshow . ');"';

				$pic_preview = 'onmouseover="showtrail(\'' . append_sid(album_append_uid('album_picm.' . PHP_EXT . '?pic_id=' . $picrow[$j]['pic_id'])) . '\',\'' . addslashes($picrow[$j]['pic_title']) . '\', ' . $album_config['midthumb_width'] . ', ' . $album_config['midthumb_height'] . ')" onmouseout="hidetrail()"';
			}

			$template_vars = array(
				'PIC_PREVIEW_HS' => $pic_preview_hs,
				'PIC_PREVIEW' => $pic_preview,
			);
			album_build_column_vars($template_vars, $picrow[$j], '&amp;sort_order=' . $sort_order . '&amp;sort_method=' . $sort_method);
			$template->assign_block_vars('picrow.piccol', $template_vars);

			// is a personal category that the picture belongs to AND
			// is it the main category in the personal gallery ?
			if (($picrow[$j]['cat_user_id'] != 0) && ($picrow[$j]['cat_id'] == album_get_personal_root_id($picrow[$j]['cat_user_id'])))
			{
				$album_page_url = 'album.' . PHP_EXT;
			}
			else
			{
				$album_page_url = 'album_cat.' . PHP_EXT;
			}

			$image_cat_url = append_sid(album_append_uid($album_page_url . '?cat_id=' . $picrow[$j]['cat_id'] . '&amp;user_id=' . $picrow[$j]['cat_user_id']));

			$template_vars = array(
				'PIC_PREVIEW_HS' => $pic_preview_hs,
				'PIC_PREVIEW' => $pic_preview,
				'CATEGORY' => $picrow[$j]['cat_title'],
				'U_PIC_CAT' => $image_cat_url,
				'GROUP_NAME' => 'all',
			);
			album_build_detail_vars($template_vars, $picrow[$j], '&amp;sort_order=' . $sort_order . '&amp;sort_method=' . $sort_method);
			$template->assign_block_vars('picrow.pic_detail', $template_vars);
		}
	}

	// --------------------------------
	// Pagination
	// --------------------------------

	$template->assign_vars(array(
		'PAGINATION' => generate_pagination(append_sid(album_append_uid('album_allpics.' . PHP_EXT . '?cat_id=' . $cat_id . '&amp;sort_method=' . $sort_method . '&amp;sort_order=' . $sort_order . $album_view_mode_param . $album_view_type_param)), $total_pics, $pics_per_page, $start),
		'PAGE_NUMBER' => sprintf($lang['Page_of'], (floor($start / $pics_per_page) + 1), ceil($total_pics / $pics_per_page))
		)
	);
}
else
{
	$template->assign_block_vars('no_pics', array());
}


/*
+----------------------------------------------------------
| Main page...
+----------------------------------------------------------
*/

// ------------------------------------
// additional sorting options
// ------------------------------------

$sort_rating_option = '';
$sort_comments_option = '';
if($album_config['rate'] == 1)
{
	$sort_rating_option = '<option value="rating" ';
	$sort_rating_option .= ($sort_method == 'rating') ? 'selected="selected"' : '';
	$sort_rating_option .= '>' . $lang['Rating'] .'</option>';
}
if($album_config['comment'] == 1)
{
	$sort_comments_option = '<option value="comments" ';
	$sort_comments_option .= ($sort_method == 'comments') ? 'selected="selected"' : '';
	$sort_comments_option .= '>' . $lang['Comments'] .'</option>';

	$sort_new_comment_option = '<option value="new_comment" ';
	$sort_new_comment_option .= ($sort_method == 'new_comment') ? 'selected="selected"' : '';
	$sort_new_comment_option .= '>' . $lang['New_Comment'] .'</option>';
}

// Start output of page
$meta_content['page_title'] = $lang['Album'];
$meta_content['description'] = '';
$meta_content['keywords'] = '';
$nav_title = (strtolower($album_view_type) == 'comment') ? $lang['All_Comment_List_Of_User'] : ((strtolower($album_view_type) == 'rating') ? $lang['All_Rating_List_Of_User'] : $lang['All_Picture_List_Of_User']);
$nav_server_url = create_server_url();
$breadcrumbs['address'] = ALBUM_NAV_ARROW . '<a href="' . $nav_server_url . append_sid('album.' . PHP_EXT) . '">' . $lang['Album'] . '</a>' . ALBUM_NAV_ARROW . '<a class="nav-current" href="' . $nav_server_url . append_sid(album_append_uid('album_allpics.' . PHP_EXT . '?mode=' . $album_view_mode . '&amp;type=' . $album_view_type)) . '">' . $nav_title . '</a>';

switch (strtolower($album_view_type))
{
	case 'comment':
		$template->assign_block_vars('switch_show_all_pics', array());
		$template->assign_block_vars('switch_show_all_ratings', array());
		$list_title = $lang['All_Comment_List_Of_User'];
		break;
	case 'rating':
		$template->assign_block_vars('switch_show_all_pics', array());
		$template->assign_block_vars('switch_show_all_comments', array());
		$list_title = $lang['All_Rating_List_Of_User'];
		break;
	default:
		$template->assign_block_vars('switch_show_all_ratings', array());
		$template->assign_block_vars('switch_show_all_comments', array());
		$list_title = $lang['All_Picture_List_Of_User'];
}

$template->assign_block_vars('switch_show_album_search', array());

$template->assign_vars(array(
	'TARGET_BLANK' => ($album_config['fullpic_popup']) ? 'target="_blank"' : '',

	'S_COLS' => $album_config['cols_per_page'],
	'S_COL_WIDTH' => (100/$album_config['cols_per_page']) . '%',
	'S_THUMBNAIL_SIZE' => $album_config['thumbnail_size'],

	'L_NO_PICTURES_BY_USER' => $lang['No_Pics'],
	'U_MEMBERLIST_GALLERY' => append_sid(album_append_uid('album_allpics.' . PHP_EXT . '?mode=' . $album_view_mode . '&amp;type=' . $album_view_type)),

	'U_SHOW_ALL_PICS' => append_sid(album_append_uid('album_allpics.' . PHP_EXT . '?' . $album_view_mode_param . '&amp;type=pic')),
	'L_SHOW_ALL_PICS' => $lang['All_Show_All_Pictures_Of_user'],
	'SHOW_ALL_PICS_IMG' => $images['show_all_pics'],

	'U_SHOW_ALL_RATINGS' => append_sid(album_append_uid('album_allpics.' . PHP_EXT . '?' . $album_view_mode_param . '&amp;type=rating')),
	'L_SHOW_ALL_RATINGS' => $lang['All_Show_All_Ratings_Of_user'],
	'SHOW_ALL_RATINGS_IMG' => $images['show_all_ratings'],

	'U_SHOW_ALL_COMMENTS' => append_sid(album_append_uid('album_allpics.' . PHP_EXT . '?' . $album_view_mode_param . '&amp;type=comment')),
	'L_SHOW_ALL_COMMENTS' => $lang['All_Show_All_Comments_Of_user'],
	'SHOW_ALL_COMMENTS_IMG' => $images['show_all_comments'],

	'L_PICTURES_OF_USER' => $list_title,

	'L_PIC_ID' => $lang['Pic_ID'],
	'L_PIC_TITLE' => $lang['Pic_Image'],
	'L_PIC_CAT' => $lang['Pic_Cat'],
	'L_POSTED' => $lang['Posted'],
	'L_VIEW' => $lang['View'],
	'L_TIME' => $lang['Time'],

	'L_SELECT_SORT_METHOD' => $lang['Select_sort_method'],
	'L_ORDER' => $lang['Order'],
	'L_SORT' => $lang['Sort'],

	'SORT_TIME' => ($sort_method == 'pic_time') ? 'selected="selected"' : '',
	'SORT_PIC_TITLE' => ($sort_method == 'pic_title') ? 'selected="selected"' : '',
	'SORT_VIEW' => ($sort_method == 'pic_view_count') ? 'selected="selected"' : '',

	'SORT_RATING_OPTION' => $sort_rating_option,
	'SORT_COMMENTS_OPTION' => $sort_comments_option,
	'SORT_NEW_COMMENT_OPTION' => $sort_new_comment_option,

	'L_ASC' => $lang['Sort_Ascending'],
	'L_DESC' => $lang['Sort_Descending'],

	'SORT_ASC' => ($sort_order == 'ASC') ? 'selected="selected"' : '',
	'SORT_DESC' => ($sort_order == 'DESC') ? 'selected="selected"' : ''
	)
);

$template_to_parse = $class_plugins->get_tpl_file(ALBUM_TPL_PATH, 'album_memberlist_body.tpl');
full_page_generation($template_to_parse, $meta_content['page_title'], $meta_content['description'], $meta_content['keywords']);

?>