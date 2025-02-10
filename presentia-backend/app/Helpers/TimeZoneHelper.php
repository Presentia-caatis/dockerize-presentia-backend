<?php

namespace App\Helpers;
use Carbon\Carbon;

if (!function_exists('convert_utc_to_timezone')) {
    function convert_utc_to_timezone($dateTime, $timeZone)
    {
        return Carbon::parse($dateTime, 'UTC')->tz($timeZone);
    }
}

if (!function_exists('convert_timezone_to_utc')) {
    function convert_timezone_to_utc($dateTime, $timeZone)
    {
        return Carbon::parse($dateTime, $timeZone)->tz('UTC');
    }
}

if (!function_exists('stringify_convert_utc_to_timezone')) {
    function stringify_convert_utc_to_timezone($dateTime, $timeZone, $format = 'Y-m-d H:i:s')
    {
        return convert_utc_to_timezone($dateTime, $timeZone)->format($format);
    }
}

if(!function_exists('stringify_convert_timezone_to_utc')){
    function stringify_convert_timezone_to_utc($dateTime, $timeZone, $format = 'Y-m-d H:i:s')
    {
        return convert_timezone_to_utc($dateTime, $timeZone)->format($format);
    }
}

if (!function_exists('convert_time_timezone_to_utc')) {
    function convert_time_timezone_to_utc($time, $timezone = "Asia/Jakarta")
    {
        return Carbon::createFromFormat('H:i', $time, $timezone)
            ->setTimezone('UTC')
            ->format('H:i');
    }
}

