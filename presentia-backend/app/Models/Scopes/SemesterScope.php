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
        $table = $model->getTable();

        if ($model->getConnection()->getSchemaBuilder()->hasColumn($table, 'semester_id')) {
            $builder->where("{$table}.semester_id", config('semester.id'));
        } else {
            $builder->whereHas('semesters', function ($query) use ($table) {
                $query->where("{$table}.semester_id", config('semester.id'));
            });
        }
    }
}
