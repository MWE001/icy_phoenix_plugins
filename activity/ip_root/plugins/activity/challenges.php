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
* aUsTiN-Inc 2003/5 (austin@phpbb-amod.com) - (http://phpbb-amod.com)
*
*/

if (!defined('IN_ICYPHOENIX'))
{
	die('Hacking attempt');
}

/* Start Restriction Checks */
BanCheck();
/* Start Game Disabled To Everyone Check */
if(($config['ina_disable_everything']) && ($user->data['user_level'] != ADMIN))
{
	message_die(GENERAL_ERROR, $lang['restriction_check_3'], $lang['ban_error']);
}
/* End Game Disabled To Everyone Check */
/* Start File Specific Disable */
if(($config['ina_disable_challenges_page']) && ($user->data['user_level'] != ADMIN))
{
	message_die(GENERAL_ERROR, $lang['disabled_page_error'], $lang['ban_error']);
}
/* End File Specific Disable */
/* End Restriction Checks */

$template_to_parse = $class_plugins->get_tpl_file(ACTIVITY_TPL_PATH, 'challenges_body.tpl');
$template->set_filenames(array('body' => $template_to_parse));

$template->assign_vars(array(
	'BACK_LINK' => 'activity.' . PHP_EXT . '?page=challenges',
	'BACK_TEXT' => $lang['top_five_4']
	)
);

if (!$_GET['mode'])
{
	$total_challenges = $config['challenges_sent'];
	$template->assign_block_vars('challenge', array(
		'TOTAL_SENT' => $lang['challenge_header_1'] . $total_challenges . $lang['challenge_header_2'],
		'CHALLENGE_NAME' => $lang['challenge_username'],
		'CHALLENGE_SPOT' => $lang['challenge_position'],
		'CHALLENGE_COUNT' => $lang['challenge_challenges']
		)
	);

	$i = 1;

	// ------------- Setup Challenge Array -------------------------------- |
	$q = "SELECT *
			FROM ". INA_CHALLENGE ."
			ORDER BY count DESC";
	$r = $db -> sql_query($q);
	$challenge_data = $db -> sql_fetchrowset($r);
	$challenge_count = $db -> sql_numrows($r);

	// ------------- Setup Users Array ------------------------------------ |
	$q = "SELECT username, user_id, user_active, user_color
			FROM ". USERS_TABLE ."";
	$r = $db -> sql_query($q);
	$user_data = $db -> sql_fetchrowset($r);
	$user_count = $db -> sql_numrows($r);

	for($a = 0; $a < $challenge_count; $a++)
	{
		for($b = 0; $b < $user_count; $b++)
		{
			if($challenge_data[$a]['user'] == $user_data[$b]['user_id'])
			{
				$name = colorize_username($user_data[$b]['user_id'], $user_data[$b]['username'], $user_data[$b]['user_color'], $user_data[$b]['user_active'], true);
				$count = number_format($challenge_data[$a]['count']);
				$target_user = $challenge_data[$a]['user'];

				if ($target_user == ANONYMOUS)
				{
					$link = '&nbsp;&nbsp;&nbsp;' . $name;
				}
				else
				{
					$link = '&nbsp;&nbsp;&nbsp;<a href="activity.' . PHP_EXT . '?page=challenges&amp;mode=check_user&amp;' . POST_USERS_URL . '=' . $target_user . '&amp;sid=' . $user->data['session_id'] . '" style="text-decoration:none">' . $name . '</a>';
				}
				$row_class = (!($i % 2)) ? 'row1' : 'row2';
				$template->assign_block_vars('challenge_results', array(
					'ROW_CLASS' => $row_class,
					'LINK' => $link,
					'PLACE' => $i,
					'COUNT' => $count
					)
				);
				$i++;
			}
		}
	}
}
else
{
	$user_check = $_GET['u'];

	$q1 = "SELECT username
			FROM ". USERS_TABLE ."
			WHERE user_id = '". $user_check ."'";
	$r1 = $db -> sql_query($q1);
	$row = $db -> sql_fetchrow($r1);
	$sender = $row['username'];

		$template->assign_block_vars("user", array(
			"USER_TO" => $lang['challenge_page_1'],
			"USER_TO_COUNT" => $lang['challenge_page_2'],
			"USER" => $sender ."'s". $lang['challenge_page_3'],
			"RANKING" => $lang['challenge_page_4'])
				);

	$c = 1;
	$i = 0;
	$q = "SELECT *
			FROM ". INA_CHALLENGE_USERS ."
			WHERE user_from = '". $user_check ."'
			ORDER BY count DESC";
	$r = $db -> sql_query($q);
	while($row = $db -> sql_fetchrow($r))
	{

		$user_to = $row['user_to'];
		$count1 = $row['count'];
		$count = number_format($count1);

		$q2 = "SELECT username
				FROM ". USERS_TABLE ."
				WHERE user_id = '". $user_to ."'";
		$r2 = $db -> sql_query($q2);
		$row = $db -> sql_fetchrow($r2);
		$receiver = $row['username'];

			$row_class = (!($i % 2)) ? 'row1' : 'row2';

			$template->assign_block_vars("user_array", array(
				"ROW_CLASS" => $row_class,
				"USER_TO" => $receiver,
				"USER_TO_COUNT" => $count,
				"RANKING" => $c)
			);
		$c++;
		$i++;
	}
}
$template->pparse('body');

/* Give credit where credit is due. */
echo ('
<script type="text/javascript">
function copyright()
{
	var popurl = \'' . ACTIVITY_PLUGIN_PATH . 'includes/functions_amod_plusC.' . PHP_EXT . '\'
	var winpops = window.open(popurl, "", "width=400, height=400,")
}
</script>
<table>
<tr>
<td>
<a style="text-decoration:none;" href="javascript:copyright();" class="gensmall">&copy; Activity Mod Plus</a>
</td>
</tr>
</table>
');
?>