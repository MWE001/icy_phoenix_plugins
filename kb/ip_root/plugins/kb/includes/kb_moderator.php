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

if(!defined('IN_ICYPHOENIX'))
{
	die('Hacking attempt');
}

include_once(IP_ROOT_PATH . 'includes/functions_admin.' . PHP_EXT);

$category_id = request_var('cat', 0);
$article_id = request_var('a', 0);
$page_id = request_var('page', 0);
$ref_stats = (isset($_GET['ref'])) ? true : false;

$start = request_var('start', 0);
$start = ($start < 0) ? 0 : $start;

// Start auth check
$kb_is_auth = array();
$kb_is_auth = kb_auth(AUTH_ALL, $category_id, $user->data);
// End of auth check

if (!(($kb_is_auth['auth_delete'] || $kb_is_auth['auth_mod']) && $user->data['session_logged_in']))
{
	$message = $lang['No_add'] . '<br /><br />' . sprintf($lang['Click_return_kb'], '<a href="' . append_sid(this_kb_mxurl()) . '">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . append_sid(IP_ROOT_PATH . CMS_PAGE_FORUM) . '">', '</a>');
	mx_message_die(GENERAL_MESSAGE, $message);
}

$action = request_var('action', '');

if (empty($action))
{
	$action = '';
	if ($approve && $kb_is_auth['auth_mod'])
	{
		$action = 'approve';
	}
	else if ($unapprove && $kb_is_auth['auth_mod'])
	{
		$action = 'unapprove';
	}
	else if ($delete && ($kb_is_auth['auth_mod'] || $kb_is_auth['auth_delete']))
	{
		$action = 'delete';
	}
}

switch ($action)
{
	case 'approve':

		$sql = "SELECT * FROM " . KB_ARTICLES_TABLE . " WHERE article_id = " . $article_id;
		$result = $db->sql_query($sql);
		$kb_row = $db->sql_fetchrow($result);
		$topic_sql = '';
		$kb_comment = array();

		// Populate the kb_comment variable
		$kb_comment = kb_get_data($kb_row, $user->data);

		// Compose post header
		$subject = $lang['KB_comment_prefix'] . $kb_comment['article_title'];
		$message_temp = kb_compose_comment($kb_comment);

		$kb_message = $message_temp['message'];
		$kb_update_message = $message_temp['update_message'];

		if ($kb_config['use_comments'])
		{
			if (!$kb_row['topic_id'])
			{
				// Post
				$topic_data = kb_insert_post($kb_message, $subject, $kb_comment['category_forum_id'], $kb_comment['article_editor_id'], $kb_comment['article_editor'], $kb_comment['article_editor_sig'], $kb_comment['topic_id'], $kb_update_message);

				$topic_sql = ", topic_id = " . $topic_data['topic_id'];
			}
		}

		$sql = "UPDATE " . KB_ARTICLES_TABLE . " SET approved = 1 " . $topic_sql . "
			WHERE article_id = " . $article_id;
		$result = $db->sql_query($sql);

		$article_category_id = $kb_row['article_category_id'];

		update_kb_number($article_category_id, '+ 1');
		kb_notify($kb_config['notify'], $kb_message, $kb_config['admin_id'], $kb_comment['article_editor_id'], 'approved');
		mx_add_search_words('single', $article_id, stripslashes($kb_row['article_body']), stripslashes($kb_row['article_title']), 'kb');

		$message = $lang['Article_approved'] . '<br /><br />' . sprintf($lang['Click_return_article_manager'], '<a href="' . append_sid(this_kb_mxurl('page=' . $page_id . '&mode=cat&cat=' . $category_id . '&start=' . $start)) . '">', '</a>') ;

		mx_message_die(GENERAL_MESSAGE, $message);
		break;

	case 'unapprove':

		$sql = "UPDATE " . KB_ARTICLES_TABLE . " SET approved = 0
			WHERE article_id = " . $article_id;
		$result = $db->sql_query($sql);

		$sql = "SELECT *
			FROM " . KB_ARTICLES_TABLE . "
			WHERE article_id = " . $article_id;
		$result = $db->sql_query($sql);


		if ($kb_row = $db->sql_fetchrow($result))
		{
			$article_category_id = $kb_row['article_category_id'];
		}

		update_kb_number($article_category_id, '- 1');
		mx_remove_search_post($article_id, 'kb');

		$message = $lang['Article_unapproved'] . '<br /><br />' . sprintf($lang['Click_return_article_manager'], '<a href="' . append_sid(this_kb_mxurl('page=' . $page_id . '&mode=cat&cat=' . $category_id . '&start=' . $start)) . '">', '</a>') ;

		mx_message_die(GENERAL_MESSAGE, $message);
		break;

	case 'delete':

		if ($_GET['c'] == "yes")
		{
			$sql = "SELECT *
				FROM " . KB_ARTICLES_TABLE . "
				WHERE article_id = " . $article_id;
			$result = $db->sql_query($sql);

			if ($article = $db->sql_fetchrow($result))
			{
				$article_category_id = $article['article_category_id'];
			}

			if ($article['approved'] == 1)
			{
				update_kb_number($article_category_id, '- 1');
			}

			if ($kb_config['del_topic'] && $article['topic_id'])
			{
				$topic = (int) $article['topic_id'];
				$forum_id = 0;
				$sql = "SELECT forum_id
					FROM " . TOPICS_TABLE . "
					WHERE topic_id = " . $topic . "
					LIMIT 1";
				$result = $db->sql_query($sql);
				$kb_row = $db->sql_fetchrow($result);
				$forum_id = (int) $kb_row['forum_id'];
				$db->sql_freeresult($result);

				if (!empty($forum_id) && !empty($topic_id))
				{
					if (!class_exists('class_mcp')) include(IP_ROOT_PATH . 'includes/class_mcp.' . PHP_EXT);
					if (empty($class_mcp)) $class_mcp = new class_mcp();
					$class_mcp->topic_delete($topic, $forum_id);
				}
			}

			$sql = "DELETE FROM  " . KB_ARTICLES_TABLE . " WHERE article_id = " . $article_id;
			$result = $db->sql_query($sql);

			$sql = "DELETE FROM  " . KB_MATCH_TABLE . " WHERE article_id = " . $article_id;
			$result = $db->sql_query($sql);

			mx_remove_search_post($article_id, 'kb');

			$message = $lang['Article_deleted'] . '<br /><br />' . sprintf($lang['Click_return_article_manager'], '<a href="' . append_sid(this_kb_mxurl('page=' . $page_id . '&mode=cat&cat=' . $category_id . '&start=' . $start)) . '">', '</a>') ;

			mx_message_die(GENERAL_MESSAGE, $message);
		}
		else
		{
			$category_id = ($ref_stats ? 1 : $category_id);

			$message = $lang['Confirm_art_delete'] . '<br /><br />' . sprintf($lang['Confirm_art_delete_yes'], '<a href="' . append_sid(this_kb_mxurl('mode=moderate&action=delete&page=' . $page_id . '&cat=' . $category_id . '&c=yes&a=' . $article_id . '&start=' . $start)) . '">', '</a>') . '<br /><br />' . sprintf($lang['Confirm_art_delete_no'], '<a href="' . append_sid(IP_ROOT_PATH . CMS_PAGE_FORUM . '?page=' . $page_id . '&mode=cat&cat=' . $category_id . '&start=' . $start) . '">', '</a>');
			mx_message_die(GENERAL_MESSAGE, $message);
		}
		break;
}

$template->pparse('body');

?>