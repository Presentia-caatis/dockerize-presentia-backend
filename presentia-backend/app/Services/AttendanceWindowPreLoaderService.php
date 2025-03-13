<?php

namespace App\Services;

use App\Models\AttendanceSchedule;
use App\Models\AttendanceWindow;
use App\Models\Day;
use App\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonInterface;


class AttendanceWindowPreLoaderService
{
    protected $event;
    protected $attendanceSchedule;

    public function __construct(Event $event, AttendanceSchedule $attendanceSchedule)
    {
        $this->event = $event;
        $this->attendanceSchedule = $attendanceSchedule;
    }

    public function apply()
    {
        $event = $this->event;
        $attendanceSchedule = $this->attendanceSchedule;


        $currentDate = Carbon::parse($event->start_date);
        $endDate = Carbon::parse($event->end_date);

        $occurrencesCount = 0;
        $interval = $event->interval ?? 1;


        while (($endDate != null && $currentDate->lessThanOrEqualTo($endDate)) || $occurrencesCount < $event->occurrences) {

            $datesToProcess = [];

            switch ($event->recurring_frequency) {
                case "daily":
                    $datesToProcess[] = $currentDate;
                    while (($endDate != null && $currentDate->lessThanOrEqualTo($endDate)) || $occurrencesCount < $event->occurrences) {
                        $datesToProcess[] = $currentDate;
                        $occurrencesCount++;
                        $currentDate = $this->getNextRecurrenceDate($event, $currentDate);
                    }
                    break;
                case "daily_exclude_holiday":
                    $datesToProcess[] = $currentDate;
                    $holiday = Day::whereHas("attendanceSchedule", function ($query) {
                        $query->where("type", "holiday");
                    })->get()->pluck("name")->toArray();
                    while (($endDate != null && $currentDate->lessThanOrEqualTo($endDate)) || $occurrencesCount < $event->occurrences) {
                        if (in_array($currentDate->format("l"), $holiday)) {
                            $currentDate = $this->getNextRecurrenceDate($event, $currentDate);
                            continue;
                        }
                        $datesToProcess[] = $currentDate;
                        $occurrencesCount++;
                        $currentDate = $this->getNextRecurrenceDate($event, $currentDate);
                    }
                    break;
                case "weekly":
                    $datesToProcess[] = $this->getDaysOfWeek($currentDate);
                    break;
                case "monthly":
                    $datesToProcess[] = $this->getDaysOfMonth($currentDate);
                    break;
                case "yearly":
                    $datesToProcess[] = $currentDate;
                    break;
            }


            foreach ($datesToProcess as $date) {
                if ($endDate && $date->greaterThan($endDate)) {
                    continue;
                }

                AttendanceWindow::create([
                    'event_id' => $event->id,
                    'school_id' => $event->school_id,
                    'day_id' => $date->dayOfWeek + 1,
                    'name' => $attendanceSchedule->name,
                    'date' => $date->toDateString(),
                    'type' => $attendanceSchedule->type,
                    'check_in_start_time' => $attendanceSchedule->check_in_start_time,
                    'check_in_end_time' => $attendanceSchedule->check_in_end_time,
                    'check_out_start_time' => $attendanceSchedule->check_out_start_time,
                    'check_out_end_time' => $attendanceSchedule->check_out_end_time,
                ]);
            }

            $occurrencesCount++;
            // Move to the next recurrence date
            $currentDate = $this->getNextRecurrenceDate($event, $currentDate);
        }
    }

    private function getDaysOfWeek(Carbon $currentDate)
    {
        $weekStart = $currentDate->copy()->startOfWeek(CarbonInterface::SUNDAY);
        $weekEnd = $currentDate->copy()->endOfWeek(CarbonInterface::SATURDAY);

        $desiredDays = $event->days_of_week ?? [];
        $datesToProcess = [];
        for ($date = $weekStart->copy(); $date->lessThanOrEqualTo($weekEnd) && $date->lessThanOrEqualTo(Carbon::parse($this->event->end_date)); $date->addDay()) {
            if (in_array(strtolower($date->format('l')), $desiredDays)) {
                $datesToProcess[] = $date;
            }
        }

        return $datesToProcess;
    }
    private function getDaysOfMonth(Carbon $date)
    {
        $datesToProcess = [];
        if ($this->event->days_of_month) {
            foreach ($this->event->days_of_month as $day) {
                if ($day > 0) {
                    $datesToProcess[] = $date->copy()->day($day);
                } elseif ($day < 0) {
                    $datesToProcess[] = $date->endOfMonth()->addDays($day + 1);
                }
            }
        } else if (!empty($this->event->weeks_of_month) && !empty($this->event->days_of_week)) {
            foreach ($this->event->weeks_of_month as $week) {
                foreach ($this->event->days_of_week as $dayOfWeek) {
                    $datesToProcess[] = $this->getSpecificWeekdayOfMonth($date, $week, $dayOfWeek);
                }
            }
        }


        return array_filter($datesToProcess);
    }

    private function getSpecificWeekdayOfMonth(Carbon $date, int $week, string $dayOfWeek)
    {
        $firstDayOfMonth = $date->copy()->startOfMonth();
        $lastDayOfMonth = $date->copy()->endOfMonth();

        if ($week > 0) {
            $targetDate = $firstDayOfMonth->copy()->next($dayOfWeek);
            $targetDate->addWeeks($week - 1);
        } else {
            $targetDate = $lastDayOfMonth->copy()->previous($dayOfWeek);
            $targetDate->subWeeks(abs($week) - 1);
        }

        return ($targetDate->month === $date->month) ? $targetDate : null;
    }

    private function getNextRecurrenceDate(Event $event, Carbon $currentDate)
    {
        $interval = $event->interval ?? 1;
        switch ($event->recurring_frequency) {
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
}