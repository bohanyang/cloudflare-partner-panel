<?php


class Translations
{
    public static array $data = [];
    public static function get($name)
    {
        if (isset(self::$data[$name])) {
            return self::$data[$name];
        }

        return $name;
    }
}

function trans($name)
{
    return Translations::get($name);
}

$language_supported = [
    'zh' => 'zh-CN'
];
$lan = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 5);
$lan = strtolower($lan);
$short_lan = substr($lan, 0, 2);
$iso_language = 'en';
if (isset($language_supported[$short_lan])) {
    $iso_language = $language_supported[$short_lan];
    if (false === $translations = apcu_fetch('cfp_translations_' . $iso_language)) {
        $translations = require __DIR__ . "/$iso_language.php";
        apcu_store('cfp_translations_' . $iso_language, $translations, 60);
    }
    Translations::$data = $translations;
}

$ttl_translate = [
	1 => trans('Automatic'),
	120 => trans('2 mins'),
	300 => trans('5 mins'),
	600 => trans('10 mins'),
	900 => trans('15 mins'),
	1800 => trans('30 mins'),
	3600 => trans('1 hour'),
	7200 => trans('2 hours'),
	18000 => trans('5 hours'),
	43200 => trans('12 hours'),
	86400 => trans('1 day'),
];
$status_translate = [
	'active' => '<span class="badge badge-success">' . trans('Active') . '</span>',
	'pending' => '<span class="badge badge-warning">' . trans('Pending') . '</span>',
	'initializing' => '<span class="badge badge-light">' . trans('Initializing') . '</span>',
	'moved' => '<span class="badge badge-dark">' . trans('Moved') . '</span>',
	'deleted' => '<span class="badge badge-danger">' . trans('Deleted') . '</span>',
	'deactivated' => '<span class="badge badge-light">' . trans('Deactivated') . '</span>',
];
$action_name = [
	'logout' => trans('Logout'),
	'security' => trans('Security'),
	'add_record' => trans('Add Record'),
	'edit_record' => trans('Edit Record'),
	'delete_record' => trans('Delete Record'),
	'add' => trans('Add Domain'),
	'zone' => trans('Manage Zone'),
	'dnssec' => trans('DNSSEC'),
	'login' => trans('Login'),
];
