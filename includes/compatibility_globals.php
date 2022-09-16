<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

/**
*/
if (!defined('IN_ENGINE'))
{
	exit;
}

//
// Deprecated globals
//
define('ATTACHMENT_CATEGORY_WM', 2); // Windows Media Files - Streaming - @deprecated 3.2
define('ATTACHMENT_CATEGORY_RM', 3); // Real Media Files - Streaming - @deprecated 3.2
define('ATTACHMENT_CATEGORY_QUICKTIME', 6); // Quicktime/Mov files - @deprecated 3.2
define('ATTACHMENT_CATEGORY_FLASH', 5); // Flash/SWF files - @deprecated 3.3

/**
 * Sets compatibility globals in the global scope
 *
 * This function registers compatibility variables to the global
 * variable scope. This is required to make it possible to include this file
 * in a service.
 */
function register_compatibility_globals()
{
	global $engine_container;

	global $cache, $engine_dispatcher, $request, $user, $auth, $db, $config, $language, $engine_log;
	global $symfony_request, $engine_filesystem, $engine_path_helper, $engine_extension_manager, $template;

	// set up caching
	/* @var $cache \phpbb\cache\service */
	$cache = $engine_container->get('cache');

	// Instantiate some basic classes
	/* @var $engine_dispatcher \phpbb\event\dispatcher */
	$engine_dispatcher = $engine_container->get('dispatcher');

	/* @var $request \phpbb\request\request_interface */
	$request = $engine_container->get('request');
	// Inject request instance, so only this instance is used with request_var
	request_var('', 0, false, false, $request);

	/* @var $user \phpbb\user */
	$user = $engine_container->get('user');

	/* @var \phpbb\language\language $language */
	$language = $engine_container->get('language');

	/* @var $auth \phpbb\auth\auth */
	$auth = $engine_container->get('auth');

	/* @var $db \phpbb\db\driver\driver_interface */
	$db = $engine_container->get('dbal.conn');

	// Grab global variables, re-cache if necessary
	/* @var $config phpbb\config\db */
	$config = $engine_container->get('config');
	set_config('', '', false, $config);
	set_config_count('', 0, false, $config);

	/* @var $engine_log \phpbb\log\log_interface */
	$engine_log = $engine_container->get('log');

	/* @var $symfony_request \phpbb\symfony_request */
	$symfony_request = $engine_container->get('symfony_request');

	/* @var $engine_filesystem \phpbb\filesystem\filesystem_interface */
	$engine_filesystem = $engine_container->get('filesystem');

	/* @var $engine_path_helper \phpbb\path_helper */
	$engine_path_helper = $engine_container->get('path_helper');

	// load extensions
	/* @var $engine_extension_manager \phpbb\extension\manager */
	$engine_extension_manager = $engine_container->get('ext.manager');

	/* @var $template \phpbb\template\template */
	$template = $engine_container->get('template');
}
