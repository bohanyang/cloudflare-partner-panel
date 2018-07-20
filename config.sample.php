<?php

$timezone = 'Asia/Shanghai';

// !!! README !!!
// !!! This PHP script use (and only use) path_info to route
// !!! Make sure you have correct $_SERVER['PATH_INFO'] or $_SERVER['ORIG_PATH_INFO'] been set
// !!!
// !!! (
// !!!   If you use NGiNX web server, remember to use fastcgi_split_path_info and
// !!!   set $fastcgi_path_info as fastcgi_param PATH_INFO
// !!! )
// !!!
// !!! Search on the web what PATH_INFO means if you have no idea
// !!! Sorry for my willfulness...

// If you have done the rewrite from /some/path to index.php
// You can set this var to empty string to enable Pretty URL (hide the /index.php)
// If not, set it to /index.php
$root = "/index.php";

$key = "YOUR_API_KEY";

// If you are using HTTP, then set this to false.
$cookie_secure = true;

// Provider Name
$provider = "Yet Another...";

// Login Page Message HTML
$defmsg = "Just use it...";
