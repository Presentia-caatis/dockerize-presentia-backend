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
        if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'school_id')) {
            $builder->where('school_id', $this->schoolId ?? config('school.id'));
        } else {
            $builder->whereHas('schools', function ($query) {
                $query->where('school_id', $this->schoolId ?? config('school.id'));
            });
        }
    }
}
