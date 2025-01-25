<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $schoolId = config('school.id');
        if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'school_id')) {
            $builder->where('school_id', $schoolId);
        } else {
            $builder->whereHas('schools', function ($query) use ($schoolId) {
                $query->where('school_id', $schoolId);
            });
        }
    }
}
