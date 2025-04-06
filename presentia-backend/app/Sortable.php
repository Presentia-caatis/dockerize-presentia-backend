<?php

namespace App;

trait Sortable
{
    public function applySort($query, $sort, $forbiddenRelations = [])
    {
        if (!is_array($sort) || empty($sort)) {
            return $query;
        }

        if (count($sort) > 1) {
            abort(422, 'Only one sorting field is allowed at a time.');
        }

        $column = array_key_first($sort);
        $direction = $sort[$column];

        if (in_array($column, $forbiddenRelations)) {
            abort(422, "Sorting by '$column' is not allowed.");
        }

        return $query->orderBy($column, strtolower($direction) === 'desc' ? 'desc' : 'asc');
    }
}
