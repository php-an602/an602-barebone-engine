#!/usr/bin/env php
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

use Symfony\Component\Console\Input\ArgvInput;

if (php_sapi_name() != 'cli')
{
	echo 'This program must be run from the command line.' . PHP_EOL;
	exit(1);
}

define('IN_ENGINE', true);

$engine_root_path = __DIR__ . '/../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
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

require($engine_root_path . 'includes/constants.' . $phpEx);
require($engine_root_path . 'includes/functions.' . $phpEx);
require($engine_root_path . 'includes/functions_admin.' . $phpEx);
require($engine_root_path . 'includes/utf/utf_tools.' . $phpEx);
require($engine_root_path . 'includes/functions_compatibility.' . $phpEx);

$engine_container_builder = new \phpbb\di\container_builder($engine_root_path, $phpEx);
$engine_container = $engine_container_builder->with_config($engine_config_php_file);

$input = new ArgvInput();

if ($input->hasParameterOption(array('--env')))
{
	$engine_container_builder->with_environment($input->getParameterOption('--env'));
}

if ($input->hasParameterOption(array('--safe-mode')))
{
	$engine_container_builder->without_extensions();
	$engine_container_builder->without_cache();
}
else
{
	$engine_class_loader_ext = new \phpbb\class_loader('\\', "{$engine_root_path}ext/", $phpEx);
	$engine_class_loader_ext->register();
}

$engine_container = $engine_container_builder->get_container();
$engine_container->get('request')->enable_super_globals();
require($engine_root_path . 'includes/compatibility_globals.' . $phpEx);

register_compatibility_globals();

/** @var \phpbb\config\config $config */
$config = $engine_container->get('config');

/** @var \phpbb\language\language $language */
$language = $engine_container->get('language');
$language->set_default_language($config['default_lang']);
$language->add_lang(array('common', 'acp/common', 'cli'));

/* @var $user \phpbb\user */
$user = $engine_container->get('user');
$user->data['user_id'] = ANONYMOUS;
$user->ip = '127.0.0.1';

$application = new \phpbb\console\application('phpBB Console', PHPBB_VERSION, $language, $config);
$application->setDispatcher($engine_container->get('dispatcher'));
$application->register_container_commands($engine_container->get('console.command_collection'));
$application->run($input);
