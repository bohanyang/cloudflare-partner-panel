<?php
/**
 * The main file
 *
 * @file    $Source: /README.md  $
 * @package core
 * @author  ZE3kr <ze3kr@icloud.com>
 *
 */

$starttime = microtime(true);
$page_title = null;
$version = '1.2.3';

require __DIR__ . '/settings.php';
require __DIR__ . '/cloudflare.class.php';

/**
 * Add resources hint header for HTTP/2 Push.
 *
 * @param string $uri the relative URI for the file to push
 * @param string $as the file type (script, style, image, etc)
 */
function h2push(string $uri, string $as) {
	global $tlo_path, $is_debug;
	if (isset($tlo_path) && !$is_debug) {
		header("Link: <{$tlo_path}{$uri}>; rel=preload; as={$as}", false);
	}
}

if (!isset($_COOKIE['tlo_cached_main'])) {
	h2push('assets/style.css', 'style');
	h2push('assets/app.js', 'script');
	h2push('assets/favicon.ico', 'image');
	setcookie('tlo_cached_main', 1);
}

if (isset($_GET['action']) && $_GET['action'] === 'zone' && !isset($_COOKIE['tlo_cached_cloud'])) {
	h2push('assets/cloud_on.png', 'image');
	h2push('assets/cloud_off.png', 'image');
	setcookie('tlo_cached_cloud', 1);
}

if (!isset($_COOKIE['user_key']) || !isset($_COOKIE['cloudflare_email']) || !isset($_COOKIE['user_api_key'])) {
	$_GET['action'] = 'login';
	if (isset($_POST['cloudflare_email']) && isset($_POST['cloudflare_pass'])) {
		$cloudflare_email = $_POST['cloudflare_email'];
		$cloudflare_pass = $_POST['cloudflare_pass'];
		$cloudflare = new CloudFlare($host_key);
		$res = $cloudflare->userCreate($cloudflare_email, $cloudflare_pass);
		$times = apcu_fetch('login_' . date("Y-m-d H") . $cloudflare_email);
		if ($times > 5) {
			$msg = '<p>' . trans('You have been blocked since you have too many fail logins. You can try it in next hour.') . '</p>';
		} elseif ($res['result'] == 'success') {
			if (isset($_POST['remember'])) {
				$cookie_time = time() + 31536000; // Expired in 365 days.
			} else {
				$cookie_time = 0;
			}
			setcookie('cloudflare_email', $res['response']['cloudflare_email'], $cookie_time);
			setcookie('user_key', $res['response']['user_key'], $cookie_time);
			setcookie('user_api_key', $res['response']['user_api_key'], $cookie_time);

			header('Location: ./');
		} else {
			$times = $times + 1;
			apcu_store('login_' . date("Y-m-d H") . $cloudflare_email, $times, 7200);
			$msg = $res['msg'];
		}
	}
} else {
	if (isset($_GET['action']) && $_GET['action'] === 'logout') { 
		setcookie('cloudflare_email', '', time() - 86400);
		setcookie('user_key', '', time() - 86400);
		setcookie('user_api_key', '', time() - 86400);
	}
	$key = new \Cloudflare\API\Auth\APIKey($_COOKIE['cloudflare_email'], $_COOKIE['user_api_key']);
	$adapter = new Cloudflare\API\Adapter\Guzzle($key);
}
?><!DOCTYPE html>
<html <?php if (isset($iso_language)) {echo 'lang="' . $iso_language . '"';}?>>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?php
if (isset($_GET['action'])) {
	if ($_GET['action'] != 'login') {
		if (isset($action_name[$_GET['action']])) {
			echo $action_name[$_GET['action']] . ' | ';
			if (isset($_GET['domain'])) {
				echo $_GET['domain'] . ' | ';
			}
		}
	} else {
		echo $action_name[$_GET['action']] . ' | ';
	}
} else {
	echo trans('Console') . ' | ';
}

echo trans('Cloudflare CNAME/IP Advanced Setup') . ' - ' . $page_title;
?></title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/css/bootstrap.min.css" integrity="sha256-aAr2Zpq8MZ+YA/D6JtRD3xtrwpEz2IqOS+pWD/7XKIw=" crossorigin="anonymous">
	<link rel="stylesheet" href="assets/style.css">
	<link rel="icon" type="image/x-icon" href="assets/favicon.ico">
</head>
<body class="bg-light">
	<nav class="navbar navbar-expand-sm navbar-dark bg-dark">
		<a class="navbar-brand" href="./"><?php echo $page_title; ?></a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarSupportedContent">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item active nav-link">
					<?php if (isset($_GET['action']) && isset($action_name[$_GET['action']])) {echo $action_name[$_GET['action']];} else {echo trans('Console');}?> <span class="sr-only">(current)</span>
				</li>
				<?php if (!isset($_GET['action']) || $_GET['action'] != 'login' && $_GET['action'] != 'logout') {?>
				<li class="nav-item">
					<a class="nav-link" href="?action=logout"><?php echo trans('Logout'); ?></a>
				</li>
				<?php }?>
			</ul>
		</div>
	</nav>
	<main class="bg-white">
<?php
$cloudflare = new CloudFlare($host_key);
if (isset($_GET['action'])) {
	$action = $_GET['action'];
} else {
	$action = false;
}

switch ($action) {
case 'logout':
	require __DIR__ . '/actions/logout.php';
	break;
case 'dnssec':
	require __DIR__ . '/actions/dnssec.php';
	break;
case 'add_record':
	require __DIR__ . '/actions/add_record.php';
	break;
case 'edit_record':
	require __DIR__ . '/actions/edit_record.php';
	break;
case 'delete_record':
	require __DIR__ . '/actions/delete_record.php';
	break;
case 'add':
	require __DIR__ . '/actions/add.php';
	break;
case 'zone':
	require __DIR__ . '/actions/zone.php';
	break;
case 'security':
	require __DIR__ . '/actions/security.php';
	break;
case 'login':
	require __DIR__ . '/actions/login.php';
	break;
default:
	require __DIR__ . '/actions/list_zones.php';
	break;
}
?>
	</main>
<?php
if (isset($is_debug) && $is_debug) {
	$time = round(microtime(true) - $starttime, 3);
	echo '<footer class="footer"><small><p>Load time: ' . $time . 's </p></footer>';
}
?>
	<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha256-4+XzXVhsDmqanXGHaHvgh1gMQKX40OUvDEBTu8JcmNs=" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js" integrity="sha256-Xt8pc4G0CdcRvI0nZ2lRpZ4VHng0EoUDMlGcBSQ9HiQ=" crossorigin="anonymous"></script>
	<script src="assets/app.js"></script>
</body>
</html>
