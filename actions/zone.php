<?php
/*
 * Zone setup page
 */

if (!isset($adapter)) {exit;}

$zone_name = $_GET['domain'];
if (!isset($_GET['page'])) {
	$_GET['page'] = 1;
}
$dns = new Cloudflare\API\Endpoints\DNS($adapter);
$zones = new Cloudflare\API\Endpoints\Zones($adapter);

$zoneID = $_GET['zoneid'];

try {
	$dnsresult_data = $dns->listRecords($zoneID, '', '', '', intval($_GET['page']));
} catch (Exception $e) {
	exit('<div class="alert alert-danger" role="alert">' . $e->getMessage() . '</div>');
}

$dnsresult = $dnsresult_data->result;

foreach ($dnsresult as $record) {
	$dnsids[$record->id] = true;
	$dnsproxyied[$record->id] = $record->proxied;
	$dnstype[$record->id] = $record->type;
	$dnscontent[$record->id] = $record->content;
	$dnsname[$record->id] = $record->name;
	$dnscheck[$record->name] = true;
}
?>
<strong><?php echo '<h1 class="h5"><a href="?action=zone&amp;domain=' . $zone_name . '&amp;zoneid=' . $zoneID . '">' . strtoupper($zone_name) . '</a></h1>'; ?></strong>
<hr><?php
/* Toggle the CDN */
if (isset($_GET['enable']) && !$dnsproxyied[$_GET['enable']]) {
	if ($dns->updateRecordDetails($zoneID, $_GET['enable'], ['type' => $dnstype[$_GET['enable']], 'content' => $dnscontent[$_GET['enable']], 'name' => $dnsname[$_GET['enable']], 'proxied' => true])->success == true) {
		echo '<p class="alert alert-success" role="alert">' . trans('Success') . '! </p>';
	} else {
		echo '<p class="alert alert-danger" role="alert">' . trans('Failed') . '! </p><p><a href="?action=zone&amp;domain=' . $zone_name . '&amp;zoneid=' . $zoneID . '">' . trans('Go to console') . '</a></p>';
		exit();
	}
} else {
	$_GET['enable'] = 1;
	if (isset($_GET['disable']) && $dnsproxyied[$_GET['disable']]) {
		if ($dns->updateRecordDetails($zoneID, $_GET['disable'], ['type' => $dnstype[$_GET['disable']], 'content' => $dnscontent[$_GET['disable']], 'name' => $dnsname[$_GET['disable']], 'proxied' => false])->success == true) {
			echo '<p class="alert alert-success" role="alert">' . trans('Success!') . '</p>';
		} else {
			echo '<p class="alert alert-danger" role="alert">' . trans('Failed') . '! </p><p><a href="?action=zone&amp;domain=' . $zone_name . '&amp;zoneid=' . $zoneID . '">' . trans('Go to console') . '</a></p>';
			exit();
		}
	} else {
		$_GET['disable'] = 1;
	}
}
?>
<div class="btn-group dropright">
	<button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		<?php echo trans('Contents'); ?>
	</button>
	<div class="dropdown-menu">
		<a class="dropdown-item" href="#dns"><?php echo trans('DNS Management'); ?></a>
		<a class="dropdown-item" href="#cname"><?php echo trans('CNAME Setup'); ?></a>
		<a class="dropdown-item" href="#ip"><?php echo trans('IP Setup'); ?></a>
		<a class="dropdown-item" href="#ns"><?php echo trans('NS Setup'); ?></a>
		<div class="dropdown-divider"></div>
		<a class="dropdown-item" href="https://dash.cloudflare.com/" target="_blank"><?php echo trans('More Settings'); ?></a>
	</div>
</div>
<h3 class="mt-5 mb-3" id="dns"><?php echo trans('DNS Management'); ?><a class="btn btn-primary float-sm-right d-block mt-3 mt-sm-0" href='?action=add_record&amp;zoneid=<?php echo $zoneID; ?>&amp;domain=<?php echo $zone_name; ?>'><?php echo trans('Add New Record'); ?></a></h3>
<table class="table table-striped">
	<thead>
		<tr>
			<th scope="col" class="d-none d-md-table-cell"><?php echo trans('Record Type'); ?></th>
			<th scope="col"><?php echo trans('Host Name'); ?></th>
			<th scope="col" class="d-none d-md-table-cell"><?php echo trans('Content'); ?></th>
			<th scope="col" class="d-none d-md-table-cell"><?php echo trans('TTL'); ?></th>
			<th scope="col" class="d-none d-md-table-cell"><?php echo trans('Operation'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
$no_record_yet = true;
$dnsClient = new \Spatie\Dns\Dns();
$dnsClient->useNameserver('ns3.cloudflare.com');
/** @var \Spatie\Dns\Records\Record[] $resp */
$resp = $dnsClient->getRecords($zone_name, 'NS');
foreach ($dnsresult as $record) {
	if ($record->proxiable) {
		if ($record->proxied) {
			$proxiable = '<a href="?action=zone&domain=' . $zone_name . '&disable=' . $record->id . '&page=' . $_GET['page'] . '&amp;zoneid=' . $zoneID . '"><img src="assets/cloud_on.png" height="19"></a>';
			if (!isset($resp_a) && !isset($resp_aaaa)) {
                /** @var \Spatie\Dns\Records\Record[] $resp_a */
                $resp_a =$dnsClient->getRecords("$record->name.cdn.cloudflare.net", 'A');
                /** @var \Spatie\Dns\Records\Record[] $resp_aaaa */
                $resp_aaaa = $dnsClient->getRecords("$record->name.cdn.cloudflare.net", 'AAAA');
			}
		} else {
			$proxiable = '<a href="?action=zone&domain=' . $zone_name . '&enable=' . $record->id . '&page=' . $_GET['page'] . '&amp;zoneid=' . $zoneID . '"><img src="assets/cloud_off.png" height="30"></a>';
		}
	} else {
		$proxiable = '<img src="assets/cloud_off.png" height="30">';
	}
	if (isset($_GET['enable']) && $record->id === $_GET['enable']) {
		$proxiable = '<a href="?action=zone&domain=' . $zone_name . '&disable=' . $record->id . '&page=' . $_GET['page'] . '&amp;zoneid=' . $zoneID . '"><img src="assets/cloud_on.png" height="19"></a>';
	} elseif (isset($_GET['disable']) && $record->id === $_GET['disable']) {
		$proxiable = '<a href="?action=zone&domain=' . $zone_name . '&enable=' . $record->id . '&page=' . $_GET['page'] . '&amp;zoneid=' . $zoneID . '"><img src="assets/cloud_off.png" height="30"></a>';
	}
	if ($record->type == 'MX') {
		$priority = '<code>' . $record->priority . '</code> ';
	} else {
		$priority = '';
	}
	if (isset($ttl_translate[$record->ttl])) {
		$ttl = $ttl_translate[$record->ttl];
	} else {
		$ttl = $record->ttl . ' s';
	}
	$no_record_yet = false;
	echo '<tr>
		<td class="d-none d-md-table-cell"><code>' . $record->type . '</code></td>
		<td scope="col">
			<div class="d-block d-md-none float-right">' . $proxiable . '</div>
			<div class="d-block d-md-none">' . $record->type . ' ' . trans('record') . '</div>
			<code>' . htmlspecialchars($record->name) . '</code>
			<div class="d-block d-md-none">' . trans('points to') . ' ' . '<code>' . htmlspecialchars($record->content) . '</code></div>
			<div class="btn-group dropleft float-right d-block d-md-none" style="margin-top:-1em;">
				<button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				' . trans('Manage') . '
				</button>
				<div class="dropdown-menu">
					<a class="dropdown-item" href="?action=edit_record&domain=' . $zone_name . '&recordid=' . $record->id . '&zoneid=' . $zoneID . '">' . trans('Edit') . '</a>
					<a class="dropdown-item" href="?action=delete_record&domain=' . $zone_name . '&delete=' . $record->id . '&zoneid=' . $zoneID . '" onclick="return confirm(\'' . trans('Are you sure to delete') . ' ' . htmlspecialchars($record->name) . '?\')">' . trans('Delete') . '</a>
				</div>
			</div>
			<div class="d-block d-md-none">' . trans('TTL') . ' ' . $ttl . '</div>
		</td>
		<td class="d-none d-md-table-cell">' . $priority . '<code>' . htmlspecialchars($record->content) . '</code></td>
		<td class="d-none d-md-table-cell">' . $ttl . '</td>
		<td class="d-none d-md-table-cell" style="width: 200px;">' . $proxiable . ' |
			<div class="btn-group" role="group">
				<a class="btn btn-dark btn-sm" href="?action=edit_record&domain=' . $zone_name . '&recordid=' . $record->id . '&zoneid=' . $zoneID . '">' . trans('Edit') . '</a>
				<a class="btn btn-danger btn-sm" href="?action=delete_record&domain=' . $zone_name . '&delete=' . $record->id . '&zoneid=' . $zoneID . '" onclick="return confirm(\'' . trans('Are you sure to delete') . ' ' . htmlspecialchars($record->name) . '?\')">' . trans('Delete') . '</a>
			</div>
		</td>
	</tr>';
}
?>
	</tbody>
</table><?php

if ($no_record_yet) {
	echo '<div class="alert alert-warning" role="alert">' . trans('There is no record in this zone yet. Please add some!') . '</div>';
}

if (isset($dnsresult_data->result_info->total_pages)) {
	$previous_page = '';
	$next_page = '';
	if ($dnsresult_data->result_info->page < $dnsresult_data->result_info->total_pages) {
		$page_link = $dnsresult_data->result_info->page + 1;
		$next_page = ' | <a href="?action=zone&domain=' . $zone_name . '&page=' . $page_link . '&amp;zoneid=' . $zoneID . '">' . trans('Next') . '</a>';
	}
	if ($dnsresult_data->result_info->page > 1) {
		$page_link = $dnsresult_data->result_info->page - 1;
		$previous_page = '<a href="?action=zone&domain=' . $zone_name . '&page=' . $page_link . '&amp;zoneid=' . $zoneID . '">' . trans('Previous') . '</a> | ';
	}
	echo '<p>' . $previous_page . trans('Page') . ' ' . $dnsresult_data->result_info->page . '/' . $dnsresult_data->result_info->total_pages . $next_page . '</p>';
}
?>
<p><?php echo trans('You can use CNAME, IP or NS to set it up.'); ?></p>

<h3 class="mt-5 mb-3" id="cname"><?php echo trans('CNAME Setup'); ?></h3>
<table class="table table-striped">
	<thead>
		<tr>
			<th scope="col"><?php echo trans('Host Name'); ?></th>
			<th scope="col" class="d-none d-md-table-cell">CNAME</th>
		</tr>
	</thead>
	<tbody>
		<?php
$avoid_cname_duplicated = [];
foreach ($dnsresult as $record) {
	if (!isset($avoid_cname_duplicated[$record->name])) {
		echo '<tr>
				<td scope="col"><code>' . $record->name . '</code>
					<div class="d-block d-md-none">' . trans('points to') . ' <code>' . $record->name . '.cdn.cloudflare.net</code></div>
				</td>
				<td class="d-none d-md-table-cell"><code>' . $record->name . '.cdn.cloudflare.net</code></td>
				</tr>';
		$avoid_cname_duplicated[$record->name] = true;
	}
}
?>
	</tbody>
</table><?php

if ($no_record_yet) {
	echo '<div class="alert alert-warning" role="alert">' . trans('There is no record in this zone yet. Please add some!') . '</div>';
}

if (isset($dnsresult_data->result_info->total_pages)) {
	$previous_page = '';
	$next_page = '';
	if ($dnsresult_data->result_info->page < $dnsresult_data->result_info->total_pages) {
		$page_link = $dnsresult_data->result_info->page + 1;
		$next_page = ' | <a href="?action=zone&domain=' . $zone_name . '&page=' . $page_link . '">' . trans('Next') . '</a>';
	}
	if ($dnsresult_data->result_info->page > 1) {
		$page_link = $dnsresult_data->result_info->page - 1;
		$previous_page = '<a href="?action=zone&domain=' . $zone_name . '&page=' . $page_link . '">' . trans('Previous') . '</a> | ';
	}
	echo '<p>' . $previous_page . trans('Page') . ' ' . $dnsresult_data->result_info->page . '/' . $dnsresult_data->result_info->total_pages . $next_page . '</p>';
}

if (!empty($resp_a) || !empty($resp_aaaa)):
	?>

<h3 class="mt-5 mb-3" id="ip"><?php echo trans('IP Setup'); ?></h3>
<?php if (!empty($resp_a)): ?>
<h4>Anycast IPv4</h4>
<ul>
	<?php foreach ($resp_a as $answer): ?>
	<li><code><?php echo $answer->ip(); ?></code></li>
	<?php endforeach; ?>
</ul>
<?php endif; if (!empty($resp_aaaa)): ?>
<h4>Anycast IPv6</h4>
<ul>
	<?php foreach ($resp_aaaa as $answer): ?>
	<li><code><?php echo $answer->ipv6(); ?></code></li>
	<?php endforeach; ?>
</ul>
<?php endif; endif; ?>

<?php if (!empty($resp)) {?>
<h3 class="mt-5 mb-3" id="ns"><?php echo trans('NS Setup'); ?></h3>
<table class="table table-striped">
	<thead>
		<tr>
			<th scope="col"><?php echo trans('Host Name'); ?></th>
			<th class="d-none d-md-table-cell">NS</th>
		</tr>
	</thead>
	<tbody>
    <?php foreach ($resp as $answer): ?>
        <tr>
            <td><code><?php echo $zone_name; ?></code>
                <div class="d-block d-md-none"><?php echo trans('points to') . ' <code>' . $answer->target() . '</code>' ?></div>
            </td>
            <td class="d-none d-md-table-cell"><code><?php echo $answer->target(); ?></code></td>
        </tr>
    <?php endforeach; ?>

	</tbody>
</table>
<?php }?>
