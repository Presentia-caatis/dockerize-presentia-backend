<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    protected $schoolId;

    public function __construct($schoolId = null)
    {
        $this->schoolId = $schoolId;
    }

    public function apply(Builder $builder, Model $model)
    {
        $table = $model->getTable();
        if ($model->getConnection()->getSchemaBuilder()->hasColumn($table, 'school_id')) {
            $builder->where("{$table}.school_id", $this->schoolId ?? config('school.id'));
        } else {
            $relation = $model->schools();
            $pivotTable = method_exists($relation, 'getTable') ? $relation->getTable() : 'school_id';
    
            $builder->whereHas('schools', function ($query) use ($pivotTable) {
                $query->where("{$pivotTable}.school_id", $this->schoolId ?? config('school.id'));
            });
        }
    }
}
