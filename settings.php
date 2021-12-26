<?php

require __DIR__ . '/vendor/autoload.php';

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

require __DIR__ . '/languages/translates.php';
require __DIR__ . '/config.php';

if (!isset($host_key) || $host_key === false) exit(trans('No HOST_KEY or HOST_MAIL defined in config.php .'));

$page_title = (isset($page_title) && $page_title !== false) ? $page_title : 'Cloudflare';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Content-Type: text/html; charset=utf-8");
