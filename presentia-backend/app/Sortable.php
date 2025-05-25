<?php

namespace App;

use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait Sortable
{
    /**
     * Apply sorting to the query based on the provided sort array.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $sort Example: ['student.classGroup.name' => 'asc']
     * @param array $forbiddenRelations List of relations or columns that are restricted from sorting
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applySort($query, $sort, $forbiddenRelations = [])
    {
        // Return early if no sort is requested
        if (empty($sort)) {
            return $query;
        }

        // Only one sorting column is allowed at a time
        if (count($sort) > 1) {
            abort(422, 'Only one sorting field is allowed at a time.');
        }

        $model = $query->getModel();
        $modelTable = $model->getTable();

        // Remove the global school scope and reapply manually to avoid ambiguity
        $query->withoutGlobalScope(SchoolScope::class);

        $schoolId = auth()->user()->school_id ?? null;
        if ($schoolId) {
            // Apply qualified where condition to avoid ambiguity between joined tables
            $query->where($modelTable . '.school_id', $schoolId);
        }

        // Get the sort column and direction
        $column = array_key_first($sort);
        $direction = strtolower($sort[$column]) === 'desc' ? 'desc' : 'asc';

        // Reject if the field is directly forbidden
        if (in_array($column, $forbiddenRelations)) {
            abort(422, "Sorting by '$column' is not allowed.");
        }

        // Handle nested relation sorting like: student.classGroup.name
        if (str_contains($column, '.')) {
            $relations = explode('.', $column);
            $field = array_pop($relations); // Extract the final field to sort on

            $currentModel = $model;
            $previousAlias = $modelTable; // Start with the base table
            $aliasPrefix = ''; // Used to create unique aliases for nested joins

            foreach ($relations as $relation) {
                // Check if this relation is forbidden
                if (in_array($relation, $forbiddenRelations)) {
                    throw ValidationException::withMessages([
                        'sort' => "Sorting by relation '$relation' is not allowed."
                    ]);
                }

                // Validate the relation method exists on the model
                if (!method_exists($currentModel, $relation)) {
                    abort(422, "Invalid relation '$relation' on model " . get_class($currentModel));
                }

                $relationObj = $currentModel->$relation();

                // Only allow sorting through BelongsTo or HasOne relations
                if (!$relationObj instanceof BelongsTo && !$relationObj instanceof HasOne) {
                    abort(422, "Sorting only allowed on BelongsTo or HasOne. '$relation' is not.");
                }

                // Prepare join parameters
                $related = $relationObj->getRelated();
                $relatedTable = $related->getTable();
                $foreignKey = $relationObj->getForeignKeyName(); // FK on the current model
                $ownerKey = $relationObj->getOwnerKeyName();     // PK on the related model

                // Create a unique alias for the join to prevent collisions
                $alias = $aliasPrefix . Str::snake($relation) . '_sort';
                $aliasPrefix = $alias . '_'; // Update prefix for next level

                // Perform the left join using table aliases to avoid ambiguity
                $query->leftJoin("$relatedTable as $alias", "$previousAlias.$foreignKey", '=', "$alias.$ownerKey");

                // Move to the next level
                $currentModel = $related;
                $previousAlias = $alias;
            }

            // Apply the final sort using the last alias and extracted field
            return $query->orderBy("$previousAlias.$field", $direction);
        }

        // Basic column sort on the main table
        return $query->orderBy("$modelTable.$column", $direction);
    }
}
