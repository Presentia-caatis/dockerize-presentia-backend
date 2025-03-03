<?php

namespace App\Models;

use App\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;
    use BelongsToSchool;

    protected $fillable = [
        'name',
        'school_id',
        'is_holiday',
        'scheduler_active',
        'occurrences',
        'start_date',
        'is_active',
        'end_date',
        'recurring_frequency',
        'day_of_month',
        'days_of_week',
        'weeks_interval',
        'months_interval'
    ];

    protected $casts = [
        'day_of_month' => 'array', // Store JSON as an array
        'days_of_week' => 'array',
    ];

    public function attendanceSchedule()
    {
        return $this->hasOne(AttendanceSchedule::class);
    }

    public function isOccurringOn($date)
    {
        $date = Carbon::parse($date);

        // ✅ 1. Check if scheduler is active
        if (!$this->is_active) {
            return false;
        }

        // ✅ 2. Check event duration
        if ($this->occurrences === 0 && !$date->isSameDay($this->start_date)) {
            return false; // One-time event
        }

        if ($this->end_date && $date->gt(Carbon::parse($this->end_date))) {
            return false; // End date restriction
        }

        // ✅ 3. Handle recurring events
        switch ($this->recurring_frequency) {
            case 'daily':
                return true;

            case 'daily_exclude_holiday':
                return !$this->isHolidayOn($date);

            case 'weekly':
                if (!$this->days_of_week)
                    return false;
                return in_array(strtolower($date->format('l')), $this->days_of_week) &&
                    $this->isValidInterval($date, 'weeks', $this->weeks_interval);

            case 'monthly':
                return $this->isMatchingMonthlyDate($date);

            case 'none':
                return $date->isSameDay(Carbon::parse($this->start_date));
        }

        return false;
    }

    /**
     * Check if the date falls on a holiday (you need a holiday-checking mechanism)
     */
    private function isHolidayOn($date)
    {
        $day = strtolower(Carbon::parse($date)->format('l'));

        $dayData = Day::where('name', $day)
            ->first();
        return Day::join('attendance_schedules', 'days.attendance_schedule_id', '=', 'attendance_schedules.id')->where('attendance_schedules.type', 'holiday'
        )->select('days.*')->get();
    }

    /**
     * Check if the event follows the correct interval (weekly or monthly)
     */
    private function isValidInterval($date, $unit, $interval)
    {
        $startDate = Carbon::parse($this->start_date);
        return $startDate->diffInUnits($date, $unit) % $interval === 0;
    }

    /**
     * Check if the given date matches a valid monthly recurring rule
     */
    private function isMatchingMonthlyDate($date)
    {
        if (!$this->day_of_month)
            return false;

        $dayOfMonth = $date->day;
        $lastDayOfMonth = $date->copy()->endOfMonth()->day;

        foreach ($this->day_of_month as $day) {
            if ($day > 0 && $day == $dayOfMonth)
                return true;
            if ($day < 0 && ($lastDayOfMonth + $day + 1) == $dayOfMonth)
                return true; // Counting from end
        }

        return $this->isValidInterval($date, 'months', $this->months_interval);
    }
}
