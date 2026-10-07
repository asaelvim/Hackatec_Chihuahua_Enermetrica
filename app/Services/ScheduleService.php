<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Schedule;
use Illuminate\Support\Carbon;

class ScheduleService
{
    /**
     * Resolve the status a device should have right now, based on active
     * schedules. A device manually set to "maintenance" keeps that status
     * regardless of schedules.
     */
    public function determineStatus(Device $device): string
    {
        if ($device->status === 'maintenance') {
            return 'maintenance';
        }

        return $this->hasActiveShutdownSchedule($device) ? 'off' : 'on';
    }

    /**
     * A shutdown schedule is "active" when it currently applies to the
     * device (by scope: device > area > company) and the current day/time
     * falls within its configured window.
     */
    protected function hasActiveShutdownSchedule(Device $device): bool
    {
        $now = Carbon::now();
        $weekday = $now->dayOfWeek;
        $time = $now->format('H:i:s');

        return Schedule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($device) {
                $query->where('scope', 'company')
                    ->orWhere(fn ($q) => $q->where('scope', 'area')->where('area_id', $device->area_id))
                    ->orWhere(fn ($q) => $q->where('scope', 'device')->where('device_id', $device->id));
            })
            ->get()
            ->contains(fn (Schedule $schedule) => $this->matchesWeekday($schedule, $weekday)
                && $this->matchesTimeWindow($schedule, $time));
    }

    protected function matchesWeekday(Schedule $schedule, int $weekday): bool
    {
        return in_array((string) $weekday, explode(',', $schedule->weekdays), true);
    }

    protected function matchesTimeWindow(Schedule $schedule, string $time): bool
    {
        if ($schedule->end_time === null) {
            return $time >= $schedule->start_time;
        }

        return $time >= $schedule->start_time && $time < $schedule->end_time;
    }
}
