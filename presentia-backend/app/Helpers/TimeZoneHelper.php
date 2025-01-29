<?php

namespace App\Helpers;
use Carbon\Carbon;

if (!function_exists('convert_utc_to_timezone')) {
    function convert_utc_to_timezone($dateTime, $timeZone, $format = 'Y-m-d H:i:s') {
        return Carbon::parse(Carbon::parse($dateTime, 'UTC')->tz($timeZone)->format($format));
    }
}

if (!function_exists('convert_timezone_to_utc')) {
    function convert_timezone_to_utc($dateTime, $timeZone, $format = 'Y-m-d H:i:s') {
        return Carbon::parse(Carbon::parse($dateTime, $timeZone)->tz('UTC')->format($format));
    }
}
