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
* @ignore
*/
define('IN_ENGINE', true);
$engine_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

// Thank you sun.
if (isset($_SERVER['CONTENT_TYPE']))
{
	if ($_SERVER['CONTENT_TYPE'] === 'application/x-java-archive')
	{
		exit;
	}
}
else if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Java') !== false)
{
	exit;
}

if (isset($_GET['avatar']))
{
	require($engine_root_path . 'includes/startup.' . $phpEx);

	require($engine_root_path . 'phpbb/class_loader.' . $phpEx);
	$engine_class_loader = new \phpbb\class_loader('phpbb\\', "{$engine_root_path}phpbb/", $phpEx);
	$engine_class_loader->register();

	$engine_config_php_file = new \phpbb\config_php_file($engine_root_path, $phpEx);
	extract($engine_config_php_file->get_all());

	if (!defined('PHPBB_ENVIRONMENT'))
	{
		@define('PHPBB_ENVIRONMENT', 'production');
	}

	if (!defined('PHPBB_INSTALLED') || empty($dbms) || empty($acm_type))
	{
		exit;
	}

	require($engine_root_path . 'includes/constants.' . $phpEx);
	require($engine_root_path . 'includes/functions.' . $phpEx);
	require($engine_root_path . 'includes/functions_download' . '.' . $phpEx);
	require($engine_root_path . 'includes/utf/utf_tools.' . $phpEx);

	// Setup class loader first
	$engine_class_loader_ext = new \phpbb\class_loader('\\', "{$engine_root_path}ext/", $phpEx);
	$engine_class_loader_ext->register();

	// Set up container
	$engine_container_builder = new \phpbb\di\container_builder($engine_root_path, $phpEx);
	$engine_container = $engine_container_builder->with_config($engine_config_php_file)->get_container();

	$engine_class_loader->set_cache($engine_container->get('cache.driver'));
	$engine_class_loader_ext->set_cache($engine_container->get('cache.driver'));

	// set up caching
	/* @var $cache \phpbb\cache\service */
	$cache = $engine_container->get('cache');

	/* @var $engine_dispatcher \phpbb\event\dispatcher */
	$engine_dispatcher = $engine_container->get('dispatcher');

	/* @var $request \phpbb\request\request_interface */
	$request	= $engine_container->get('request');

	/* @var $db \phpbb\db\driver\driver_interface */
	$db			= $engine_container->get('dbal.conn');

	/* @var $engine_log \phpbb\log\log_interface */
	$engine_log	= $engine_container->get('log');

	unset($dbpasswd);

	/* @var $config \phpbb\config\config */
	$config = $engine_container->get('config');

	// load extensions
	/* @var $engine_extension_manager \phpbb\extension\manager */
	$engine_extension_manager = $engine_container->get('ext.manager');

	// worst-case default
	$browser = strtolower($request->header('User-Agent', 'msie 6.0'));

	/* @var $engine_avatar_manager \phpbb\avatar\manager */
	$engine_avatar_manager = $engine_container->get('avatar.manager');

	$filename = $request->variable('avatar', '');
	$avatar_group = false;
	$exit = false;

	if (isset($filename[0]) && $filename[0] === 'g')
	{
		$avatar_group = true;
		$filename = substr($filename, 1);
	}

	// '==' is not a bug - . as the first char is as bad as no dot at all
	if (strpos($filename, '.') == false)
	{
		send_status_line(403, 'Forbidden');
		$exit = true;
	}

	if (!$exit)
	{
		$ext		= substr(strrchr($filename, '.'), 1);
		$stamp		= (int) substr(stristr($filename, '_'), 1);
		$filename	= (int) $filename;
		$exit = set_modified_headers($stamp, $browser);
	}
	if (!$exit && !in_array($ext, array('png', 'gif', 'jpg', 'jpeg')))
	{
		// no way such an avatar could exist. They are not following the rules, stop the show.
		send_status_line(403, 'Forbidden');
		$exit = true;
	}


	if (!$exit)
	{
		if (!$filename)
		{
			// no way such an avatar could exist. They are not following the rules, stop the show.
			send_status_line(403, 'Forbidden');
		}
		else
		{
			send_avatar_to_browser(($avatar_group ? 'g' : '') . $filename . '.' . $ext, $browser);
		}
	}
	file_gc();
}

// implicit else: we are not in avatar mode
include($engine_root_path . 'common.' . $phpEx);
require($engine_root_path . 'includes/functions_download' . '.' . $phpEx);

$attach_id = $request->variable('id', 0);
$mode = $request->variable('mode', '');
$thumbnail = $request->variable('t', false);

// Start session management, do not update session page.
$user->session_begin(false);
$auth->acl($user->data);
$user->setup('viewtopic');

$engine_content_visibility = $engine_container->get('content.visibility');

if (!$config['allow_attachments'] && !$config['allow_pm_attach'])
{
	send_status_line(404, 'Not Found');
	trigger_error('ATTACHMENT_FUNCTIONALITY_DISABLED');
}

if (!$attach_id)
{
	send_status_line(404, 'Not Found');
	trigger_error('NO_ATTACHMENT_SELECTED');
}

$sql = 'SELECT attach_id, post_msg_id, topic_id, in_message, poster_id, is_orphan, physical_filename, real_filename, extension, mimetype, filesize, filetime
	FROM ' . ATTACHMENTS_TABLE . "
	WHERE attach_id = $attach_id";
$result = $db->sql_query($sql);
$attachment = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

if (!$attachment)
{
	send_status_line(404, 'Not Found');
	trigger_error('ERROR_NO_ATTACHMENT');
}
else if (!download_allowed())
{
	send_status_line(403, 'Forbidden');
	trigger_error($user->lang['LINKAGE_FORBIDDEN']);
}
else
{
	$attachment['physical_filename'] = utf8_basename($attachment['physical_filename']);

	if (!$attachment['in_message'] && !$config['allow_attachments'] || $attachment['in_message'] && !$config['allow_pm_attach'])
	{
		send_status_line(404, 'Not Found');
		trigger_error('ATTACHMENT_FUNCTIONALITY_DISABLED');
	}

	if ($attachment['is_orphan'])
	{
		// We allow admins having attachment permissions to see orphan attachments...
		$own_attachment = ($auth->acl_get('a_attach') || $attachment['poster_id'] == $user->data['user_id']) ? true : false;

		if (!$own_attachment || ($attachment['in_message'] && !$auth->acl_get('u_pm_download')) || (!$attachment['in_message'] && !$auth->acl_get('u_download')))
		{
			send_status_line(404, 'Not Found');
			trigger_error('ERROR_NO_ATTACHMENT');
		}

		// Obtain all extensions...
		$extensions = $cache->obtain_attach_extensions(true);
	}
	else
	{
		if (!$attachment['in_message'])
		{
			phpbb_download_handle_forum_auth($db, $auth, $attachment['topic_id']);

			$sql = 'SELECT forum_id, poster_id, post_visibility
				FROM ' . POSTS_TABLE . '
				WHERE post_id = ' . (int) $attachment['post_msg_id'];
			$result = $db->sql_query($sql);
			$post_row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$post_row || !$engine_content_visibility->is_visible('post', $post_row['forum_id'], $post_row))
			{
				// Attachment of a soft deleted post and the user is not allowed to see the post
				send_status_line(404, 'Not Found');
				trigger_error('ERROR_NO_ATTACHMENT');
			}
		}
		else
		{
			// Attachment is in a private message.
			$post_row = array('forum_id' => false);
			phpbb_download_handle_pm_auth($db, $auth, $user->data['user_id'], $attachment['post_msg_id']);
		}

		$extensions = array();
		if (!extension_allowed($post_row['forum_id'], $attachment['extension'], $extensions))
		{
			send_status_line(403, 'Forbidden');
			trigger_error(sprintf($user->lang['EXTENSION_DISABLED_AFTER_POSTING'], $attachment['extension']));
		}
	}

	$download_mode = (int) $extensions[$attachment['extension']]['download_mode'];
	$display_cat = $extensions[$attachment['extension']]['display_cat'];

	if (($display_cat == ATTACHMENT_CATEGORY_IMAGE || $display_cat == ATTACHMENT_CATEGORY_THUMB) && !$user->optionget('viewimg'))
	{
		$display_cat = ATTACHMENT_CATEGORY_NONE;
	}

	/**
	* Event to modify data before sending file to browser
	*
	* @event core.download_file_send_to_browser_before
	* @var	int		attach_id			The attachment ID
	* @var	array	attachment			Array with attachment data
	* @var	int		display_cat			Attachment category
	* @var	int		download_mode		File extension specific download mode
	* @var	array	extensions			Array with file extensions data
	* @var	string	mode				Download mode
	* @var	bool	thumbnail			Flag indicating if the file is a thumbnail
	* @since 3.1.6-RC1
	* @changed 3.1.7-RC1	Fixing wrong name of a variable (replacing "extension" by "extensions")
	*/
	$vars = array(
		'attach_id',
		'attachment',
		'display_cat',
		'download_mode',
		'extensions',
		'mode',
		'thumbnail',
	);
	extract($engine_dispatcher->trigger_event('core.download_file_send_to_browser_before', compact($vars)));

	if ($thumbnail)
	{
		$attachment['physical_filename'] = 'thumb_' . $attachment['physical_filename'];
	}
	else if ($display_cat == ATTACHMENT_CATEGORY_NONE && !$attachment['is_orphan'] && !phpbb_http_byte_range($attachment['filesize']))
	{
		// Update download count
		phpbb_increment_downloads($db, $attachment['attach_id']);
	}

	if ($display_cat == ATTACHMENT_CATEGORY_IMAGE && $mode === 'view' && (strpos($attachment['mimetype'], 'image') === 0) && (strpos(strtolower($user->browser), 'msie') !== false) && !phpbb_is_greater_ie_version($user->browser, 7))
	{
		wrap_img_in_html(append_sid($engine_root_path . 'download/file.' . $phpEx, 'id=' . $attachment['attach_id']), $attachment['real_filename']);
		file_gc();
	}
	else
	{
		// Determine the 'presenting'-method
		if ($download_mode == PHYSICAL_LINK)
		{
			// This presenting method should no longer be used
			if (!@is_dir($engine_root_path . $config['upload_path']))
			{
				send_status_line(500, 'Internal Server Error');
				trigger_error($user->lang['PHYSICAL_DOWNLOAD_NOT_POSSIBLE']);
			}

			redirect($engine_root_path . $config['upload_path'] . '/' . $attachment['physical_filename']);
			file_gc();
		}
		else
		{
			send_file_to_browser($attachment, $config['upload_path'], $display_cat);
			file_gc();
		}
	}
}
