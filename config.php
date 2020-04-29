<?php

$host_key = getenv('CLOUDFLARE_HOST_API_KEY');
$page_title = getenv('CLOUDFLARE_HOST_TITLE');  // Optional. Should not use HTML special character.
$tlo_path = '/'; // Optional. The installation path for this panel, ending with '/'. Required for HTTP/2 Push.
$is_debug = false; // Enable debug mode
