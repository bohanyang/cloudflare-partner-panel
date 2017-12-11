<?php

class CloudFlare
{
	private $key;

	public function __construct($key) {
		$this->key = $key;
	}

	public function postData($data) {
		$data['host_key'] = $this->key;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.cloudflare.com/host-gw.html');
		curl_setopt($ch, CURLOPT_VERBOSE, 1); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_AUTOREFERER,    TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$res = curl_exec($ch);
		curl_close($ch);
		return json_decode($res,true);
	}

	public function userCreate($cloudflare_email,$cloudflare_pass)
	{
		$data['act'] = 'user_create';
		$data['cloudflare_email'] = $cloudflare_email;
		$data['cloudflare_pass'] = $cloudflare_pass;
		$data['unique_id'] = NULL;
		$res = $this->postData($data);
		return $res;
	}

	public function userLookup()
	{
		$data['act'] = 'user_lookup';
		$data['cloudflare_email'] = $_COOKIE['cloudflare_email'];
		$res = $this->postData($data);
		return $res;
	}

	public function zoneLookup($zone_name)
	{
		$data['act'] = 'zone_lookup';
		$data['user_key'] = $_COOKIE['user_key'];
		$data['zone_name'] = $zone_name;
		$res = $this->postData($data);
		if ( $res['response']['zone_exists'] == true ) {
			return $res;
		} else {
			die("Invalid Operation. Please double check the domain you have entered.");
		}
	}

	public function zoneSet($zone_name,$resolve_to,$subdomains)
	{
		$data['act'] = 'zone_set';
		$data['user_key'] = $_COOKIE['user_key'];
		$data['zone_name'] = $zone_name;
		$data['resolve_to'] = $resolve_to;
		$data['subdomains'] = $subdomains;
		$res = $this->postData($data);
		return $res;
	}
	
	public function zoneDelete($zone_name)
	{
		$data['act'] = 'zone_delete';
		$data['user_key'] = $_COOKIE['user_key'];
		$data['zone_name'] = $zone_name;
		$res = $this->postData($data);
		return $res;
	}
}
