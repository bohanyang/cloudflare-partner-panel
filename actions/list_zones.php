<?php
/*
 *  List zones. (Home page)
 */

if (!isset($adapter)) {exit;}

if (!isset($_GET['page'])) {
	$_GET['page'] = 1;
}
?>
<a href="?action=add" class="btn btn-primary float-sm-right mb-3 d-block"><?php echo trans('Add Domain'); ?></a>
<h3 class="d-none d-sm-block"><?php echo trans('Home'); ?></h3>

<table class="table table-striped">
	<thead>
	<tr>
		<th scope="col"><?php echo trans('Domain'); ?></th>
		<th scope="col" class="d-none d-sm-table-cell"><?php echo trans('Status'); ?></th>
		<th scope="col" class="d-none d-sm-table-cell"><?php echo trans('Operation'); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
$zones = new \Cloudflare\API\Endpoints\Zones($adapter);
try {
	$zones_data = $zones->listZones('', '', intval($_GET['page']));
} catch (Exception $e) {
	exit('<div class="alert alert-danger" role="alert">' . $e->getMessage() . '</div>');
}

foreach ($zones_data->result as $zone) {
	echo '<tr>';
	$_translate_manage = trans('Manage');
	$_translate_manage_dns = trans('Manage DNS');
	$_translate_security = trans('Security');
	if (property_exists($zone, 'name_servers')) {
		echo <<<HTML
		<td scope="col">
			<div class="dropleft d-inline float-right d-sm-none">
				<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					{$_translate_manage}
				</button>
				<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
					<a class="dropdown-item" href="https://dash.cloudflare.com/" target="_blank">{$_translate_manage_dns}</a>
				</div>
			</div>
			{$zone->name}
			<span class="d-block d-sm-none"> {$status_translate[$zone->status]}</span>
		</td>
HTML;
	} else {
		echo <<<HTML
		<td scope="col">
			<div class="dropleft d-inline float-right d-sm-none">
				<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					{$_translate_manage}
				</button>
				<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
					<a class="dropdown-item" href="?action=zone&amp;domain={$zone->name}&amp;zoneid={$zone->id}">{$_translate_manage_dns}</a>
					<a class="dropdown-item" href="?action=security&amp;domain={$zone->name}&amp;zoneid={$zone->id}">{$_translate_security}</a>
				</div>
			</div>
			{$zone->name}
			<span class="d-block d-sm-none"> {$status_translate[$zone->status]}</span>
			</div>
		</td>
HTML;

	}

	echo <<<HTML
		<td class="d-none d-sm-table-cell">{$status_translate[$zone->status]}</td>
		<td class="d-none d-sm-table-cell btn-group" role="group">
HTML;
	echo <<<HTML
<a href="?action=zone&amp;domain={$zone->name}&amp;zoneid={$zone->id}" class="btn btn-secondary btn-sm">{$_translate_manage_dns}</a>
HTML;
    echo <<<HTML
<a href="?action=security&amp;domain={$zone->name}&amp;zoneid={$zone->id}" class="btn btn-dark btn-sm">{$_translate_security}</a>
HTML;
	echo '</td>';
}
?>
	</tbody>
</table><?php
if (isset($zones_data->result_info->total_pages)) {
	$previous_page = '';
	$next_page = '';
	if ($zones_data->result_info->page < $zones_data->result_info->total_pages) {
		$page_link = $zones_data->result_info->page + 1;
		$next_page = ' | <a href="?page=' . $page_link . '">' . trans('Next') . '</a>';
	}
	if ($zones_data->result_info->page > 1) {
		$page_link = $zones_data->result_info->page - 1;
		$previous_page = '<a href="?page=' . $page_link . '">' . trans('Previous') . '</a> | ';
	}
	echo '<p>' . $previous_page . trans('Page') . ' ' . $zones_data->result_info->page . '/' . $zones_data->result_info->total_pages . $next_page . '</p>';
}
