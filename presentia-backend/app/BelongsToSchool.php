<?php

namespace App;

use App\Models\Scopes\SchoolScope;

trait BelongsToSchool
{
    public static function bootBelongsToSchool()
    {
        static::addGlobalScope(new SchoolScope(self::getCurrentSchoolId()));
    }

    protected static function getCurrentSchoolId()
    {
        return config('school.id');
    }
    
}
