<?php

namespace App\Helpers;

use App\Models\Semester;

if (!function_exists('current_semester_id')) {
    function current_semester_id()
    {
        return config('semester.id');
    }
}

if (!function_exists('current_semester')) {
    function current_semester()
    {
        return Semester::findOrFail(current_semester_id());
    }
}