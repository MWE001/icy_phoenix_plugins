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

/* Start Version Check */
	VersionCheck();
/*  End Version Check */

/* Start Restriction Checks */
		BanCheck();
/* End Restriction Checks */

		$game_name = (!empty($_POST['game_name'])) ? urldecode($_POST['game_name']) : urldecode($_GET['game_name']);
		$p_search = (!empty($_POST['player_search'])) ? urldecode($_POST['player_search']) : urldecode($_GET['player_search']);

		$p_search = str_replace("%APOS%", "'", $p_search);
		$p_search = stripslashes($p_search);
		$p_search = addslashes($p_search);

		if($p_search)
		{
			$template_to_parse = $class_plugins->get_tpl_file(ACTIVITY_TPL_PATH, 'activity_scores.tpl');
			$template->set_filenames(array('body' => $template_to_parse));

			$template->assign_vars(array(
				'TITLE' => '',
				'L_HIGHSCORE' => $lang['highscore_games'],
				'L_SCORE' => stripslashes($p_search) .'\'s ' . $lang['game_score'] . 's',
				'L_PLAYED' => $lang['game_played'],
				'WIDTH1' => '',
				'WIDTH2' => '',
				'WIDTH3' => '',
				'DASH' => ''
				)
			);


				$sql = "SELECT *
					FROM ". iNA_SCORES ."
					WHERE player = '". $p_search ."'
					GROUP BY game_name
					ORDER BY game_name ASC";
				$result = $db->sql_query($sql);

			$i = 1;
			while($row = $db->sql_fetchrow($result))
			{

				$row_class = (!($i % 2)) ? 'row1' : 'row2';

				$q = "SELECT proper_name
					FROM ". iNA_GAMES ."
					WHERE game_name = '". $row['game_name'] ."'";
				$r = $db->sql_query($q);

				$row1 = $db->sql_fetchrow($r);

				$game_link = CheckGameImages($row['game_name'], $row1['proper_name']);
				$template->assign_block_vars('scores', array(
					'ROW_CLASS' => $row_class,
					'POS' => $i,
					'NAME' => $row1['proper_name'] . '<br />' . $game_link,
					'SCORE' => FormatScores($row['score']),
					'ALIGN' => 'center',
					'DATE' => create_date($config['default_dateformat'], $row['date'], $config['board_timezone'])
					)
				);
				$i++;
			}
		}
		else
		{
			$sql = "SELECT *
					FROM " . iNA_GAMES . "
						WHERE game_name = '" . $game_name . "'";
			$result = $db->sql_query($sql);

			$game_info = $db->sql_fetchrow($result);

			$highscore_limit = $game_info['highscore_limit'];

			$template_to_parse = $class_plugins->get_tpl_file(ACTIVITY_TPL_PATH, 'activity_scores.tpl');
			$template->set_filenames(array('body' => $template_to_parse));
			$template->assign_vars(array(
				'TITLE' => $game_info['proper_name'],
				'L_HIGHSCORE' => $lang['game_highscores'],
				'L_SCORE' => $lang['game_score'],
				'WIDTH1' => '',
				'WIDTH2' => '200',
				'WIDTH3' => '200',
				'DASH' => $lang['game_dash']
				)
			);

			if ($game_info['reverse_list'])
				$list_type = 'ASC';
			else
				$list_type = 'DESC';

			if (!empty($highscore_limit))
				{
				$sql = "SELECT *, MAX(score) AS hscore
				FROM ". iNA_SCORES ."
							WHERE game_name = '". $game_name ."'
				GROUP BY player
							ORDER BY score $list_type
				LIMIT 0, $highscore_limit";
				}
			else
				{
				$sql = "SELECT *, MAX(score) AS hscore
		 		FROM ". iNA_SCORES ."
							WHERE game_name = '". $game_name ."'
				GROUP BY player
							ORDER BY score $list_type";
			}
			$result = $db->sql_query($sql);

			if ($row = $db->sql_fetchrow($result))
				{
				$i = 1;
				do
				{
					$row_class = (!($i % 2)) ? 'row1' : 'row2';

					$template->assign_block_vars('scores', array(
						'ROW_CLASS' => $row_class,
						'POS' => $i,
						'NAME' => '<a href="activity.' . PHP_EXT . '?page=high_scores&amp;mode=highscore&amp;player_search=' . urlencode(str_replace("'", "%APOS%", $row['player'])) .'&amp;sid=' . $user->data['session_id'] . '">' . $row['player'] .'</a>',
						'SCORE' => FormatScores($row['hscore']),
						'ALIGN' => 'left',
						'DATE' => create_date($config['default_dateformat'], $row['date'], $config['board_timezone'])
						)
					);
					if ($row['user_plays'] > 0)
					{
						$template->assign_block_vars('scores.scores_stats', array(
							'STATS' => Amod_Individual_Game_Time($row['user_plays'], $row['play_time'])
							)
						);
					}
						$i++;
				}
				while ($row = $db->sql_fetchrow($result));
			}
		}
$template->pparse('body');

/* Give credit where credit is due. */
echo ('
<script type="text/javascript">
function copyright()
{
	var popurl = \'' . ACTIVITY_PLUGIN_PATH . 'includes/functions_amod_plusC.php\'
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