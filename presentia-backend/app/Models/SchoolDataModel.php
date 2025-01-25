<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolDataModel extends Model
{
    public function resolveRouteBinding($value, $field = null){
        return $this->where('school_id', request()->segment(2))
                    ->where('id', $value)
                    ->firstOrFail();
    }
}
