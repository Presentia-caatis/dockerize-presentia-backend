<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SemesterScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'semester_id')) {
            $builder->where('semester_id', config('semester.id'));
        } else {
            $builder->whereHas('semesters', function ($query) {
                $query->where('semester_id', config('semester.id'));
            });
        }
    }
}
