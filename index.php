<?php

require_once 'config.php';
require_once 'cloudflare.class.php';

date_default_timezone_set($timezone);

function parse_path_info ($path_info = null)
{
    if (preg_match('/^\/(?:(.*[^\/])(\/?))?$/', $path_info, $captured) && !empty($captured[1])) {
        $exploded = explode('/', $captured[1]);
        if (empty($captured[2])) {
            return [$exploded, false];
        }
        return [$exploded, true];
    }
    return [];
}

function redir ($loc, $code = 303)
{
    header("Location: " . $loc, true, $code);
    exit();
}

$raw_path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : (isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : "");

$nodes = parse_path_info($raw_path_info);
$home = !isset($nodes[0]);
if (!$home && !$nodes[1]) {
    redir($root . $raw_path_info . '/', 301);
}
$action = $home ? "" : $nodes[0][0];
if (!isset($_COOKIE['user_key']) && ($action !== "login")) {
    redir($root . '/login/', 303);
}

$cloudflare = new CloudFlare($key);

if ($action === "login" && isset($_POST['submit'])) {
    $cloudflare_email = $_POST['cloudflare_email'];
    $cloudflare_pass = $_POST['cloudflare_pass'];
    $res = $cloudflare->userCreate($cloudflare_email, $cloudflare_pass);
    if ($res['result'] == 'success') {
        setcookie('cloudflare_email', $res['response']['cloudflare_email'], time() + 900, $root . '/', "", $cookie_secure, true);
        setcookie('user_key', $res['response']['user_key'], time() + 900, $root . '/', "", $cookie_secure, true);
        redir($root . '/', 303);
    } else {
        $msg = $res['msg'];
    }
}
?>
<!DOCTYPE HTML>
<html>

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $provider; ?> × Cloudflare・DNS Console</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.loli.net/ajax/libs/amazeui/2.7.2/css/amazeui.min.css" integrity="sha256-QKNFQcDYZ0j2Vh4QhAzg711B2Ps81YW5Lte6SDm8NYM=" crossorigin="anonymous">
</head>

<body>
    <div class="am-container">
        <div class="am-margin-top-sm">
            <div class="am-text-lg"><strong class="am-text-primary"><a href="<?php echo $root; ?>/"><?php echo $provider; ?> × Cloudflare</a></strong>・DNS Console</div>
        </div>
    </div>
    <hr>
    <div class="am-container">
        <?php
        switch ($action) {
            case 'add':
                if (isset($_POST['submit'])) {
                    $zone_name = $_POST['domain'];
                    $res = $cloudflare->zoneSet($zone_name, 'example.com', 'www');
                        if ($res['result'] == 'success') {
                            $msg = 'Added successfully. Click <a href="' . $root . '/"><strong>here</strong></a> to return to the domain list.';
                        } else {
                            $msg = $res['msg'] . ' Click <a href=""><strong>here</strong></a> to try again.';
                        }
                } else {
        ?>
        <form method="POST" action="" class="am-form am-form-horizontal">
            <div class="am-form-group">
                <input type="text" id="doc-ipt-3" name="domain" placeholder="Domain" required>
            </div>
            <div class="am-form-group">
                <button type="submit" name="submit" class="am-btn am-btn-primary am-round">Add</button>
            </div>
        </form>

        <?php
            }
            break;
        case 'del':
            $zone_name = $_POST['domain'];
            $res = $cloudflare->zoneDelete($zone_name);
            if ($res['response']['zone_deleted'] == true) {
                $msg = 'Deleted successfully. Click <a href="' . $root . '/"><strong>here</strong></a> to return to the domain list.';
            } else {
                $msg = $res['msg'] . ' ' . $zone_name;
            }
            break;
        case 'zones':
            $zone_name = $nodes[0][1];
            $res = $cloudflare->zoneLookup($zone_name);
        ?>
        <strong><?php echo strtoupper($zone_name); ?></strong>
        <a class="am-btn am-btn-sm am-btn-primary am-round" href="<?php echo $root; ?>/edit/<?php echo $zone_name; ?>/">Edit</a>
        <hr>
        <div class="am-scrollable-horizontal">
            <table class="am-table am-table-striped am-table-hover am-table-striped am-text-nowrap">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Origin</th>
                        <th>CNAME Record</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($res['response']['hosted_cnames'] as $key => $cnames) {
                            echo '<tr><td>' . $key . '</td><td>' . $cnames . '</td><td>' . $res['response']['forward_tos']["$key"] . '</td></tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
            break;
        case 'edit':
            $zone_name = $nodes[0][1];
            $res = $cloudflare->zoneLookup($zone_name);
            $zoneAll = "";
            foreach($res['response']['hosted_cnames'] as $key => $cnames) {
                $key = $key != $zone_name ? substr($key, 0, strlen($key) - strlen($zone_name) - 1) : false;
                if ($key != false) {
                    $zoneAll .= $key . ' ' . $cnames . "\n";
                }
            }
            $zoneAll = substr($zoneAll, 0, -1);
            $root_res = $res['response']['hosted_cnames']["$zone_name"];
            if (isset($_POST['submit'])) {
                $subdomains = explode("\n", $_POST['subdomains']);
                $dataSet = [];
                foreach ($subdomains as $subdomain) {
                    if (!empty($subdomain)) {
                        $data = explode(' ', $subdomain);
                        if (!empty($data[0]) && !empty($data[1])) {
                            $dataSet[] = trim($data[0]) . ':' . trim($data[1]);
                        }
                    }
                }
                $postRaw = implode(',', $dataSet);
                $root_resolving = $_POST['root_resolving'];
                $res = $cloudflare->zoneSet($zone_name, $root_resolving, $postRaw);
                if ($res['result'] == 'success') {
                    $msg = 'Updated successfully. Click <a href="' . $root . '/zones/' . $zone_name . '/"><strong>here</strong></a> to return to the record list.';
                } else {
                    $msg = $res['msg'];
                }
            } else {
        ?>
        <form method="POST" action="" class="am-form">
            <fieldset>
                <legend>DNS Records</legend>
                    <div class="am-form-group">
                        <label for="doc-ipt-email-1">Origin for <?php echo $zone_name; ?></label>
                        <input type="text" name="root_resolving" class="" value="<?php echo $root_res; ?>" required>
                    </div>
                    <div class="am-form-group">
                        <label for="doc-ta-1">&lt;subdomain&gt; &lt;origin&gt;</label>
                        <textarea name="subdomains" class="" rows="5" id="doc-ta-1" required><?php echo $zoneAll; ?></textarea>
                    </div>
                    <p><button type="submit" name="submit" class="am-btn am-btn-primary am-round">Submit All</button></p>
            </fieldset>
        </form>
        <?php
            }
            break;
        case "":
            $res = $cloudflare->userLookup();
        ?>
        <p><a href="<?php echo $root; ?>/add/" class="am-btn am-btn-success am-round">Add a Domain</a></p>
        <table class="am-table am-table-striped am-table-hover">
            <thead><tr><th>Domain</th><th>Manage</th><th>Delete</th></tr></thead>
            <tbody>
                <?php
                    if (!empty($res['response']['hosted_zones'])) {
                        foreach($res['response']['hosted_zones'] as $key => $value) {
                            echo '<tr><td>' . $value . '</td><td><a class="am-btn am-btn-sm am-btn-primary am-round" href="' . $root . '/zones/' . $value . '/">Manage</a></td><td><form class="am-form" action="' . $root . '/del/" method="POST"><input type="hidden" name="domain" value="' . $value . '"><button class="am-btn am-btn-sm am-btn-danger am-round" type="submit">Delete</button></form></td></tr>';
                        }
                    }
                ?>
            </tbody>
        </table>
        <?php
            break;
        case "login":
            if (empty($msg)) {
                $res['result'] = 'success';
                $msg = $defmsg;
        ?>
        <form method="POST" action="" class="am-form am-form-horizontal">
            <div class="am-form-group">
                <input type="email" id="doc-ipt-3" name="cloudflare_email" placeholder="Email">
            </div>
            <div class="am-form-group">
                <input type="password" id="doc-ipt-pwd-2" name="cloudflare_pass" placeholder="Password">
            </div>
            <div class="am-form-group">
                <button type="submit" name="submit" class="am-btn am-btn-primary am-round">Submit</button>
            </div>
        </form>
        <?php
            }
            break;
        default:
            ?>
        <div class="am-alert am-alert-danger">
            <p>404 Not Found</p>
        </div>
            <?php
            break;
        }
        ?>
    </div>
    <div class="am-container">
        <div class="am-alert am-alert-<?php echo (!empty($res['result']) && $res['result'] == 'success') ? "success" : "danger"; echo !empty($msg) ? "" : " am-hide"; ?>">
            <p><?php echo !empty($msg) ? $msg : ""; ?></p>
        </div>
    </div>
    <hr>
    <div class="am-container">
        <p>Originated by <a href="http://www.hostloc.com/thread-386441-1-1.html" target="_blank">WeiUZ</a>. Refined by <a href="https://github.com/bohanco/cloudflare-partner-panel" target="_blank">Bohan Yang</a>.</p>
    </div>
</body>

</html>
