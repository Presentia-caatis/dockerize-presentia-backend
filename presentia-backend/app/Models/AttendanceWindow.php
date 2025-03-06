<?php

namespace App\Models;

use App\BelongsToSchool;
use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AttendanceWindow extends Model
{
    use BelongsToSchool;
    use HasFactory;
    
    protected $fillable = [
        'school_id',
        'day_id',
        'name',
        'type',
        'date',
        'check_in_start_time',
        'check_in_end_time',
        'check_out_start_time',
        'check_out_end_time',
        'scheduler_active'
    ];

    public static function validateOverlap($schoolId, $date, $checkInStart, $checkInEnd, $checkOutStart, $checkOutEnd, $ignoreId = null, $isValidateDefaultSchedule = false)
    {
        $overlappingWindows = self::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schoolId)
            ->where('date', $date)
            ->when($ignoreId, function ($query) use ($ignoreId) {
                return $query->where('id', '!=', $ignoreId);
            })
            ->get();
        
        if ($isValidateDefaultSchedule) {
            $overlappingDefaultSchedule = AttendanceSchedule::withoutGlobalScope(SchoolScope::class)
                ->where('school_id', $schoolId)
                ->where('type', 'default')
                ->get();

            $overlappingWindows = $overlappingWindows->merge($overlappingDefaultSchedule);
        }
        $errors = [];

        foreach ($overlappingWindows as $window) {
            $overlapDetails = [];

            // Check for new check-in time overlapping existing check-in time
            if (!is_null($window->check_in_start_time) && !is_null($window->check_in_end_time)) {
                if ($checkInStart < $window->check_in_end_time && $checkInEnd > $window->check_in_start_time) {
                    $overlapDetails['check_in_range'][] = [
                        'message' => "Overlapped with check-in range of Attendance Window ID {$window->id}.",
                        'data' => $window
                    ];
                }
            }

            // Check for new check-out time overlapping existing check-out time
            if (!is_null($window->check_out_start_time) && !is_null($window->check_out_end_time)) {
                if ($checkOutStart < $window->check_out_end_time && $checkOutEnd > $window->check_out_start_time) {
                    $overlapDetails['check_out_range'][] = [
                        'message' => "Overlapped with check-out range of Attendance Window ID {$window->id}.",
                        'data' => $window
                    ];
                }
            }

            // Check for new check-in time overlapping existing check-out time
            if (!is_null($window->check_out_start_time) && !is_null($window->check_out_end_time)) {
                if ($checkInStart < $window->check_out_end_time && $checkInEnd > $window->check_out_start_time) {
                    $overlapDetails['check_in_range'][] = [
                        'message' => "Check-in range overlaps with check-out range of Attendance Window ID {$window->id}.",
                        'data' => $window
                    ];
                }
            }

            // Check for new check-out time overlapping existing check-in time
            if (!is_null($window->check_in_start_time) && !is_null($window->check_in_end_time)) {
                if ($checkOutStart < $window->check_in_end_time && $checkOutEnd > $window->check_in_start_time) {
                    $overlapDetails['check_out_range'][] = [
                        'message' => "Check-out range overlaps with check-in range of Attendance Window ID {$window->id}.",
                        'data' => $window
                    ];
                }
            }

            // Merge overlap details into errors if there are any
            if (!empty($overlapDetails)) {
                $errors = array_merge_recursive($errors, $overlapDetails);
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
