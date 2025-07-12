<?php

namespace App\Services;

use App\Models\AttendanceSchedule;
use App\Models\AttendanceWindow;
use App\Models\Day;
use App\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;


class AttendanceWindowLoaderService
{
    protected $event;
    protected $attendanceSchedule;

    public function __construct(Event $event, AttendanceSchedule $attendanceSchedule)
    {
        $this->event = $event;
        $this->attendanceSchedule = $attendanceSchedule;
    }

    public function apply(bool $dryRun = false)
    {
        $event = $this->event;
        $attendanceSchedule = $this->attendanceSchedule;

        $currentDate = Carbon::parse($event->start_date);
        $endDate = $event->end_date ? Carbon::parse($event->end_date) : null;


        $datesToProcess = match ($event->recurring_frequency) {
            "one_time" => [$this->event->start_date],
            "daily" => $this->getDailyDates($currentDate, $endDate),
            "daily_exclude_holiday" => $this->getDailyExcludeHolidayDates($currentDate, $endDate),
            "weekly" => $this->getWeeklyDates($currentDate, $endDate),
            "monthly" => $this->getMonthlyDates($currentDate, $endDate),
            "yearly" => $this->getYearlyDates($currentDate, $endDate),
            default => []
        };

        if (!$dryRun) {
            foreach ($datesToProcess as $date) {
                AttendanceWindow::validateOverlap($date, 
                    $attendanceSchedule->check_in_start_time, 
                    $attendanceSchedule->check_in_end_time, 
                    $attendanceSchedule->check_out_start_time, 
                    $attendanceSchedule->check_out_end_time, 
                    null, 
                    $attendanceSchedule->event->is_scheduler_active
                );   

                AttendanceWindow::create([
                    'event_id' => $event->id,
                    'school_id' => $event->school_id,
                    'day_id' => Carbon::parse($date)->dayOfWeek + 1,
                    'name' => $attendanceSchedule->name,
                    'date' => $date,
                    'type' => $attendanceSchedule->type,
                    'check_in_start_time' => $attendanceSchedule->check_in_start_time,
                    'check_in_end_time' => $attendanceSchedule->check_in_end_time,
                    'check_out_start_time' => $attendanceSchedule->check_out_start_time,
                    'check_out_end_time' => $attendanceSchedule->check_out_end_time,
                ]);

                
            }
        } else {
            foreach ($datesToProcess as $date) {
                AttendanceWindow::validateOverlap($date, 
                    $attendanceSchedule->check_in_start_time, 
                    $attendanceSchedule->check_in_end_time, 
                    $attendanceSchedule->check_out_start_time, 
                    $attendanceSchedule->check_out_end_time, 
                    null, 
                    $attendanceSchedule->event->is_scheduler_active
                );                
            }
        }

        return $datesToProcess;
    }

    private function getDailyDates(Carbon $currentDate, ?Carbon $endDate)
    {
        $datesToProcess = [];
        $occurrencesCount = 0;
        while (($endDate && $currentDate->lessThanOrEqualTo($endDate)) || $occurrencesCount < $this->event->occurrences) {
            $datesToProcess[] = $currentDate->format('Y-m-d');
            $occurrencesCount++;
            $currentDate = $this->getNextRecurrenceDate($currentDate);
        }
        return $datesToProcess;
    }

    private function getDailyExcludeHolidayDates(Carbon $currentDate, ?Carbon $endDate)
    {
        $datesToProcess = [];
        $occurrencesCount = 0;

        $holiday = Day::whereHas("attendanceSchedule", function ($query) {
            $query->where("type", "holiday");
        })->get()->pluck("name")->toArray();

        while (($endDate && $currentDate->lessThanOrEqualTo($endDate)) || $occurrencesCount < $this->event->occurrences) {
            if (in_array(strtolower($currentDate->format("l")), $holiday)) {
                $currentDate = $this->getNextRecurrenceDate($currentDate);
                continue;
            }
            $datesToProcess[] = $currentDate->format('Y-m-d');
            $occurrencesCount++;
            $currentDate = $this->getNextRecurrenceDate($currentDate);
        }
        return $datesToProcess;
    }

    private function getWeeklyDates(Carbon $currentDate, ?Carbon $endDate)
    {
        $desiredDays = $this->event->days_of_week ?? [];
        $datesToProcess = [];
        $occurrencesCount = 0;
        while (($endDate && $currentDate->lessThanOrEqualTo($endDate)) || $occurrencesCount < $this->event->occurrences) {
            foreach ($desiredDays as $day) {
                $dayOfWeek = ucfirst(strtolower($day));
                $date = $currentDate->copy()->startOfWeek(CarbonInterface::SUNDAY)->next($dayOfWeek);
    
                if (!$endDate || $date->lessThanOrEqualTo($endDate)) {
                    $datesToProcess[] = $date->format('Y-m-d');
                }
            }
            $occurrencesCount++;
            $currentDate = $this->getNextRecurrenceDate($currentDate);
        }

        return $datesToProcess;
    }
    private function getMonthlyDates(Carbon $currentDate, ?Carbon $endDate)
    {
        $datesToProcess = [];
        $occurrencesCount = 0;
        while (($endDate && $currentDate->lessThanOrEqualTo($endDate)) || $occurrencesCount < $this->event->occurrences) {
            if ($this->event->days_of_month) {
                foreach ($this->event->days_of_month as $day) {
                    if ($day > 0 && $this->isBetweenDateLimit($currentDate->copy()->day($day))) {
                        $datesToProcess[] = $currentDate->copy()->day($day)->format('Y-m-d');
                    } elseif ($day < 0 && $this->isBetweenDateLimit($currentDate->copy()->endOfMonth()->addDays($day + 1))){
                        $datesToProcess[] = $currentDate->copy()->endOfMonth()->addDays($day + 1)->format('Y-m-d');
                    }   
                }
            } else if ($this->event->weeks_of_month && $this->event->days_of_week) {
                foreach ($this->event->weeks_of_month as $week) {
                    foreach ($this->event->days_of_week as $dayOfWeek) {
                        $datesToProcess[] = $this->getSpecificWeekdayOfMonth($currentDate, $week, $dayOfWeek)?->format('Y-m-d');
                    }
                }
            }
            $occurrencesCount++;
            $currentDate = $this->getNextRecurrenceDate($currentDate);
            
        }

        return array_filter($datesToProcess);
    }

    private function getSpecificWeekdayOfMonth(Carbon $date, int $week, string $dayOfWeek)
    {
        if ($week > 0) {
            $targetDate = $date->copy()->startOfMonth()->copy()->next($dayOfWeek);
            $targetDate->addWeeks($week - 1);
        } else {
            $targetDate = $date->copy()->endOfMonth()->copy()->previous($dayOfWeek);
            $targetDate->subWeeks(abs($week) - 1);
        }

        return ($targetDate->month === $date->month) && $this->isBetweenDateLimit($targetDate) ? $targetDate : null;
    }

    private function getYearlyDates(Carbon $currentDate, ?Carbon $endDate)
    {
        $datesToProcess = [];

        $occurrencesCount = 0;
        while (($endDate && $currentDate->lessThanOrEqualTo($endDate)) || $occurrencesCount < $this->event->occurrences) {

            if ($this->event->yearly_dates) {
                foreach ($this->event->yearly_dates as $yearlyDate) {
                    $fullDate = $currentDate->year . '-' . $yearlyDate;
                    if(!$this->isBetweenDateLimit(Carbon::parse($fullDate))) continue;
                    $datesToProcess[] = Carbon::parse($fullDate)->format('Y-m-d');
                    $occurrencesCount++;
                }
            }
            $currentDate = $this->getNextRecurrenceDate($currentDate);
        }

        return $datesToProcess;
    }

    private function getNextRecurrenceDate(Carbon $currentDate)
    {
        $interval = $this->event->interval ?? 1;
        switch ($this->event->recurring_frequency) {
            case 'daily':
                return $currentDate->addDays($interval);
            case 'daily_exclude_holiday':
                return $currentDate->addDays($interval);
            case 'weekly':
                return $currentDate->addWeeks($interval);
            case 'monthly':
                return $currentDate->addMonths($interval);
            case 'yearly':
                return $currentDate->addYears($interval);
            default:
                return $currentDate->addDay();
        }
    }

    private function isBetweenDateLimit(Carbon $date)
    {
        $startDate = Carbon::parse($this->event->start_date);
        $endDate = $this->event->end_date ? Carbon::parse($this->event->end_date) : null;
        return $date->greaterThanOrEqualTo($startDate) && (!$endDate || $date->lessThanOrEqualTo($endDate));   
    }
    
}