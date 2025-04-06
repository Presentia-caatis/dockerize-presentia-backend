<?php

namespace App;

use App\Models\Scopes\SchoolScope;

trait BelongsToSchool
{
    public static function bootBelongsToSchool()
    {
        static::addGlobalScope(new SchoolScope());
    }
}
