<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EventSchedulerService
{
  public function generateSeries(Event $baseEvent, string $repeatFrom, string $repeatTo, array $repeatDays): Collection
  {
    $events = collect();
    $repeatCode = (string) Str::uuid();
    $duration_in_minutes = Carbon::parse($baseEvent->start_at)->diffInMinutes(Carbon::parse($baseEvent->end_at));
    $numeric_days = $this->mapDaysToCarbonConstants($repeatDays);
    foreach (CarbonPeriod::create($repeatFrom, $repeatTo) as $date) {
      if (!in_array($date->dayOfWeek, $numeric_days, true)) {
        continue;
      }
      $startAt = $date->copy()->setTimeFrom($baseEvent->start_at);
      $endAt = $startAt->copy()->addMinutes($duration_in_minutes);
      $event = $baseEvent->replicate();
      $event->repeat_code = $repeatCode;
      $event->start_at = $startAt->toDateTimeString();
      $event->end_at = $endAt->toDateTimeString();
      $events->push($event);
    }
    return $events;
  }

  private function mapDaysToCarbonConstants(array $days): array
  {
    $map = [
      'monday'    => Carbon::MONDAY,
      'tuesday'   => Carbon::TUESDAY,
      'wednesday' => Carbon::WEDNESDAY,
      'thursday'  => Carbon::THURSDAY,
      'friday'    => Carbon::FRIDAY,
      'saturday'  => Carbon::SATURDAY,
      'sunday'    => Carbon::SUNDAY,
    ];
    return array_map(fn(string $day) => $map[strtolower($day)], $days);
  }
}
