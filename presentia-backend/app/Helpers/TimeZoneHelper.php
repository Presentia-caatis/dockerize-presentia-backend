<?php

namespace App\Helpers;
use Carbon\Carbon;

if (!function_exists('convert_utc_to_timezone')) {
    function convert_utc_to_timezone($dateTime, $timeZone, $format = 'Y-m-d H:i:s') {
        return Carbon::parse($dateTime)->tz($timeZone)->format($format);
    }
}
