<?php

namespace App\Services;

use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionException;

class BelongsToSchoolService
{
    protected int $schoolId;

    public function __construct(int $schoolId)
    {
        $this->schoolId = $schoolId;
    }

    /**
     * Apply the SchoolScope manually to all models using BelongsToSchool
     */
    public function apply()
    {
        foreach ($this->getModelsUsingBelongsToSchool() as $modelClass) {
            $this->applyScopeToModel($modelClass);
        }
    }

    /**
     * Get all models that use the BelongsToSchool trait
     */
    protected function getModelsUsingBelongsToSchool(): array
    {
        $models = [];
        $modelPath = app_path('Models');

        foreach (File::allFiles($modelPath) as $file) {
            $className = 'App\\Models\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
            if ($this->usesTrait($className, 'App\\BelongsToSchool')) {
                $models[] = $className;
            }
        }

        return $models;
    }

    /**
     * Apply the SchoolScope to a given model
     */
    protected function applyScopeToModel(string $modelClass)
    {
        /** @var Model $model */
        $model = new $modelClass();
        $model::addGlobalScope(new SchoolScope($this->schoolId));
    }

    /**
     * Check if a class uses a specific trait
     */
    protected function usesTrait(string $class, string $trait): bool
    {
        try {
            if (!class_exists($class)) {
                return false;
            }

            $traits = class_uses($class) ?: [];
            return in_array($trait, $traits);
        } catch (ReflectionException $e) {
            return false;
        }
    }
}
