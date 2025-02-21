<?php

namespace App;

use Illuminate\Validation\ValidationException;

trait Filterable
{
    public function applyFilters($query, $filters, $forbiddenRelations = [])
    {
        // untuk kolom yang sama dengan bukan pake like
        $exactMatchColumns = ['gender', 'class_group_id', 'school_id', 'is_active'];

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
                // Apply simple where filter
                if (is_numeric($value)) {
                    $query->where($column, $value);
                } else {
                    if (in_array($column, $exactMatchColumns)) {
                        $query->where($column, $value);
                    } else {
                        $query->where($column, 'LIKE', "%$value%");
                    }
                }
            }
        }

        return $query;
    }
}
