<?php
/**
*
* @package Icy Phoenix
* @version $Id$
* @copyright (c) 2008 Icy Phoenix
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

if (!defined('IN_ICYPHOENIX'))
{
	die('Hacking attempt');
}

define('LINKS_PLUGIN_PATH', PLUGINS_PATH . $config['plugins'][$plugin_name]['dir']);
define('LINKS_ROOT_PATH', IP_ROOT_PATH . LINKS_PLUGIN_PATH);
define('LINKS_TPL_PATH', '../../' . LINKS_PLUGIN_PATH . 'templates/');
define('LINKS_ADM_PATH', IP_ROOT_PATH . LINKS_PLUGIN_PATH . ADM . '/');
define('LINKS_ADM_TPL_PATH', '../../' . LINKS_PLUGIN_PATH . ADM . '/templates/');

$cms_page['page_id'] = $plugin_name;
$cms_page['page_nav'] = (!empty($cms_config_layouts[$cms_page['page_id']]['page_nav']) ? true : false);
$cms_page['global_blocks'] = (!empty($cms_config_layouts[$cms_page['page_id']]['global_blocks']) ? true : false);
$cms_auth_level = (isset($cms_config_layouts[$cms_page['page_id']]['view']) ? $cms_config_layouts[$cms_page['page_id']]['view'] : AUTH_ALL);
check_page_auth($cms_page['page_id'], $cms_auth_level);

if (!class_exists('class_plugins')) include(IP_ROOT_PATH . 'includes/class_plugins.' . PHP_EXT);
if (empty($class_plugins)) $class_plugins = new class_plugins();
$class_plugins->setup_lang($config['plugins'][$plugin_name]['dir']);

include(LINKS_ROOT_PATH . 'includes/functions_links.' . PHP_EXT);

?>