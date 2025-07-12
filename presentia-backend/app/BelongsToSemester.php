<?php

namespace App;

use App\Models\Scopes\SemesterScope;

trait BelongsToSemester
{
    public static function bootBelongsToSemester()
    {
        static::addGlobalScope(new SemesterScope());
    }
}
