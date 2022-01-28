<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class CloudFlare {
	private $host_key;
    private $client;

    public function __construct($host_key)
	{
		$this->host_key = $host_key;
        $this->client = new Client(['timeout' => 10]);
    }
	/**
	 * Sent a post to Cloudflare Partner API
	 * @param $data
	 * @return mixed
	 */
	public function postData(array $data) {
		$data['host_key'] = $this->host_key;
        $res = $this->client->sendRequest(new Request(
            'POST',
            'https://api.cloudflare.com/host-gw.html',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query($data)
        ));
		return json_decode($res->getBody(), true);
	}

	/**
	 * "user_create" - Create a Cloudflare account mapped to your user
	 * @see https://www.cloudflare.com/docs/host-api/#s3.2.1
	 *
	 * @param string $cloudflare_email
	 * @param string $cloudflare_pass
	 * @return mixed
	 */
	public function userCreate(string $cloudflare_email, string $cloudflare_pass) {
		$data['act'] = 'user_create';
		$data['cloudflare_email'] = $cloudflare_email;
		$data['cloudflare_pass'] = $cloudflare_pass;
		$data['unique_id'] = NULL;
		$res = $this->postData($data);
		return $res;
	}

	/**
	 * "user_lookup" - Lookup a user's Cloudflare account information
	 * @see https://www.cloudflare.com/docs/host-api/#s3.2.4
	 *
	 * @return mixed
	 */
	public function userLookup() {
		$data['act'] = 'user_lookup';
		$data['cloudflare_email'] = $_COOKIE['cloudflare_email'];
		$res = $this->postData($data);
		return $res;
	}

	/**
	 * "zone_lookup" - lookup a specific user's zone
	 * @see https://www.cloudflare.com/docs/host-api/#s3.2.6
	 *
	 * @param string $zone_name
	 * @return mixed
	 */
	public function zoneLookup(string $zone_name) {
		$data['act'] = 'zone_lookup';
		$data['user_key'] = $_COOKIE['user_key'];
		$data['zone_name'] = $zone_name;
		$res = $this->postData($data);
		if ($res['response']['zone_exists'] == true) {
			return $res;
		} else {
			die(trans('Error, please confirm your domain.'));
		}
	}

	/**
	 * "zone_set" - Setup a User's zone for CNAME hosting
	 * Use this to add a domain for CNAME setup or modify the existing CNAME domain's records.
	 * @see https://www.cloudflare.com/docs/host-api/#s3.2.2
	 *
	 * @param string $zone_name The zone you'd like to run CNAMES through Cloudflare for, e.g. "example.com".
	 * @param string $resolve_to The CNAME to origin server
	 * @param string $subdomains
	 * @return mixed
	 */
	public function zoneSet(string $zone_name, string $resolve_to, string $subdomains) {
		$data['act'] = 'zone_set';
		$data['user_key'] = $_COOKIE['user_key'];
		$data['zone_name'] = $zone_name;
		$data['resolve_to'] = $resolve_to;
		$data['subdomains'] = $subdomains;
		$res = $this->postData($data);
		return $res;
	}

	/**
	 * "full_zone_set" - Add a zone using the full setup method.
	 * Full setup is just like the domain added on the cloudflare.com. But it has ability to enable partner's Railgun.
	 * @see https://www.cloudflare.com/docs/host-api/#s3.2.3
	 *
	 * @param string $zone_name
	 * @return mixed
	 */
	public function zoneSet_full(string $zone_name) {
		$data['act'] = 'full_zone_set';
		$data['user_key'] = $_COOKIE['user_key'];
		$data['zone_name'] = $zone_name;
		$res = $this->postData($data);
		return $res;
	}

	/**
	 * "zone_delete" - delete a specific zone on behalf of a user
	 * @see https://www.cloudflare.com/docs/host-api/#s3.2.2
	 *
	 * @param $zone_name
	 * @return mixed
	 */
	public function zoneDelete($zone_name) {
		$data['act'] = 'zone_delete';
		$data['user_key'] = $_COOKIE['user_key'];
		$data['zone_name'] = $zone_name;
		$res = $this->postData($data);
		return $res;

	}
}
