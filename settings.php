<?php

require __DIR__ . '/config.php';

if (!isset($host_key) || $host_key === false) exit(_('No HOST_KEY or HOST_MAIL defined in config.php .'));

$page_title = (isset($page_title) && $page_title !== false) ? $page_title : 'Cloudflare';

/*
 * A quick fix for the server that does not support APCu Cache.
 */
if (!function_exists('apcu_fetch')) {
	function apcu_fetch() {
		return false;
	}
	function apcu_store() {
		return false;
	}
}

$language_supported = [
	'zh' => 'zh_CN.UTF-8',
];
$lan = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 5);
$lan = strtolower($lan);
$short_lan = substr($lan, 0, 2);
$dir = __DIR__ . '/languages';
$domain = 'messages';
if (isset($language_supported[$short_lan])) {
	$locale = $language_supported[$short_lan];
	$iso_language = $short_lan;
} else {
	$locale = 'en';
	$iso_language = 'en';
}
putenv('LANG=' . $locale);
if (defined('LC_MESSAGES')) {
	setlocale(LC_MESSAGES, $locale);	
}
bindtextdomain($domain, $dir);
bind_textdomain_codeset($domain, "UTF-8");
textdomain($domain);
require __DIR__ . '/languages/translates.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Content-Type: text/html; charset=UTF-8");

if (isset($is_debug) && $is_debug) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}
require __DIR__ . '/vendor/autoload.php';
