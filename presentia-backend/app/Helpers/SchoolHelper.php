<?php

namespace App\Helpers;

if (!function_exists('current_school_id')) {
    function current_school_id()
    {
        return config('school.id');
    }
}

if (!function_exists('current_school')) {
    function current_school()
    {
        return \App\Models\School::findOrFail(current_school_id());
    }
}

if (!function_exists('current_school_timezone')) {
    function current_school_timezone(){
        return current_school()->timezone;
    }
}

if (!function_exists('has_school_access')) {
    function has_school_access($schoolId)
    {
        return auth()->user()->school->id == $schoolId;
    }
}

if (!function_exists('validate_school_access')) {
    function validate_school_access($schoolId, $user)
    {
        if ($schoolId && !has_school_access($schoolId)) {
            abort(403, $user->username . ' does not have access to school data with id ' . $schoolId);
        }
    }
}