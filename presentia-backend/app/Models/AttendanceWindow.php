<?php

namespace App\Models;

use App\BelongsToSchool;
use App\BelongsToSemester;
use App\Models\Scopes\SchoolScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AttendanceWindow extends Model
{
    use BelongsToSchool, BelongsToSemester;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'event_id',
        'day_id',
        'name',
        'type',
        'date',
        'check_in_start_time',
        'check_in_end_time',
        'check_out_start_time',
        'check_out_end_time',
        'semester_id'
    ];

    public static function validateOverlap($date, $checkInStart, $checkInEnd, $checkOutStart, $checkOutEnd, $ignoreId = null, $isValidateDefaultSchedule = false)
    {
        $overlappingWindows = self::where('date', $date)
            ->when($ignoreId, function ($query) use ($ignoreId) {
                return $query->where('id', '!=', $ignoreId);
            })
            ->get()->toArray();

        $errors = [];

        if ($isValidateDefaultSchedule) {
            $defaultSchedule = AttendanceSchedule::
                whereHas('days', function ($query) use ($date) {
                    $query->where('name', Carbon::parse($date)->format('l'));
                })
                ->where('type', '=', 'default')
                    ?->first();

            if ($defaultSchedule) {
                self::checkOverlap($defaultSchedule, $checkInStart, $checkInEnd, $checkOutStart, $checkOutEnd, $errors, 0, true);
            }
            ;
        }

        $maxCheckInLateDuration = CheckInStatus::max('late_duration');

        foreach ($overlappingWindows as $window) {
            self::checkOverlap($window, $checkInStart, $checkInEnd, $checkOutStart, $checkOutEnd, $errors, $maxCheckInLateDuration);
        }


    }

    private static function checkOverlap($window, $checkInStart, $checkInEnd, $checkOutStart, $checkOutEnd, &$errors, $maxCheckInLateDuration = 0, $isValidateDefaultSchedule = false)
    {
        $recentWindowlateCutoffTime = Carbon::parse($window['check_in_end_time'])->copy()->addMinutes($maxCheckInLateDuration)->format('Y-m-d');
        $finalMessage = $isValidateDefaultSchedule ? "default Attendance Schedule" : "Attendance Window ID {$window['id']}.";

        if ($window['check_in_start_time'] && $recentWindowlateCutoffTime) {
            if ($checkInStart < $recentWindowlateCutoffTime && $checkInEnd > $window['check_in_start_time']) {
                $errors['check_in_range'][] = [
                    'message' => "Overlapped with check-in range of " . $finalMessage,
                    'data' => $window
                ];
            }
        }

        // Check for new check-out time overlapping existing check-out time
        if ($window['check_out_start_time'] && $window['check_out_end_time']) {
            if ($checkOutStart < $window['check_out_end_time'] && $checkOutEnd > $window['check_out_start_time']) {
                $errors['check_out_range'][] = [
                    'message' => "Overlapped with check-out range of " . $finalMessage,
                    'data' => $window
                ];
            }
        }

        // Check for new check-in time overlapping existing check-out time
        if ($window['check_out_start_time'] && $window['check_out_end_time']) {
            if ($checkInStart < $window['check_out_end_time'] && $checkInEnd > $window['check_out_start_time']) {
                $errors['check_in_range'][] = [
                    'message' => "Check-in range overlaps with check-out range of " . $finalMessage,
                    'data' => $window
                ];
            }
        }

        // Check for new check-out time overlapping existing check-in time
        if ($window['check_in_start_time'] && $recentWindowlateCutoffTime) {
            if ($checkOutStart < $recentWindowlateCutoffTime && $checkOutEnd > $window['check_in_start_time']) {
                $errors['check_out_range'][] = [
                    'message' => "Check-out range overlaps with check-in range of " . $finalMessage,
                    'data' => $window
                ];
            }
        }

        self::errorThrow($errors);
    }

    private static function errorThrow($errors)
    {
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function semester(){
        return $this->belongsTo(Semester::class);
    }

}
