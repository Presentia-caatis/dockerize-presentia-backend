<?php

namespace App;

use Illuminate\Validation\ValidationException;

trait Filterable
{
    public function applyFilters($query, $filters, $forbiddenRelations = [], $exactMatchColumns = [])
    {
        if (!is_array($filters) || empty($filters)) {
            return $query;
        }


        $exactMatchColumns = array_merge(['gender', 'class_group_id', 'school_id', 'is_active'], $exactMatchColumns);

        foreach ($filters as $column => $value) {
            if (strpos($column, '.') !== false) {
                // Split the relation chain
                $relations = explode('.', $column);
                $field = array_pop($relations); // Extract the actual field name

                // Check if any relation is forbidden
                foreach ($relations as $relation) {
                    if (in_array($relation, $forbiddenRelations)) {
                        throw ValidationException::withMessages([
                            'filter' => "Filtering by '{$relation}' is not allowed."
                        ]);
                    }
                }

                // Apply whereHas for nested relationships
                $query->whereHas(implode('.', $relations), function ($q) use ($field, $value) {
                    $q->where($field, 'LIKE', "%$value%");
                });
            } else {

                if (in_array($column, $exactMatchColumns)) {
                    $query->where($column, $value);
                } else {
                    $query->where($column, 'LIKE', "%$value%");
                }
            }
        }

        return $query;
    }
}
