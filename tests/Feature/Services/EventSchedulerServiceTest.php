<?php

namespace Tests\Feature\Controllers;

use App\Models\Event;
use App\Services\EventSchedulerService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EventSchedulerServiceTest extends TestCase
{

    private EventSchedulerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EventSchedulerService();
    }

    public function test_generate_series_returns_collection_with_matching_days_and_calculated_times(): void
    {
        // Prepare
        // 2026-03-02 es Lunes
        $baseEvent = new Event([
            'title' => 'Daily Meeting',
            'start_at' => '2026-03-01 09:00:00',
            'end_at' => '2026-03-01 10:30:00', // 90 min de duración
        ]);
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-08';
        $repeatDays = ['monday', 'wednesday', 'friday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertInstanceOf(Collection::class, $events);
        $this->assertCount(3, $events);
        $expectedDates = ['2026-03-02', '2026-03-04', '2026-03-06'];
        foreach ($events as $index => $event) {
            $this->assertEquals($expectedDates[$index] . ' 09:00:00', $event->start_at);
            $this->assertEquals($expectedDates[$index] . ' 10:30:00', $event->end_at);
            $this->assertEquals('Daily Meeting', $event->title);
        }
    }

    public function test_generate_series_assigns_same_uuid_repeat_code_to_all_events(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-04';
        $repeatDays = ['monday', 'tuesday', 'wednesday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertCount(3, $events);
        $repeatCode = $events->first()->repeat_code;
        $this->assertNotNull($repeatCode);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $repeatCode);
        $events->each(fn($event) => $this->assertEquals($repeatCode, $event->repeat_code));
    }

    public function test_generate_series_handles_case_insensitive_day_names(): void
    {
        // Prepare
        // 2026-03-02 es Lunes
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-02';
        $repeatDays = ['MoNdAy'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertCount(1, $events);
        $this->assertEquals('2026-03-02 10:00:00', $events->first()->start_at);
        $this->assertEquals('2026-03-02 11:00:00', $events->first()->end_at);
    }

    public function test_generate_series_returns_empty_collection_when_no_days_match_range(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatFrom = '2026-03-07'; // sabado
        $repeatTo = '2026-03-08'; // domingo
        $repeatDays = ['monday', 'tuesday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertInstanceOf(Collection::class, $events);
        $this->assertTrue($events->isEmpty());
    }

    public function test_generate_series_returns_empty_collection_when_repeat_days_array_is_empty(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-08';
        $repeatDays = [];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertTrue($events->isEmpty());
    }

    public function test_generate_series_returns_single_event_when_from_and_to_dates_are_same_day(): void
    {
        // Prepare
        // 2026-03-03 es Martes
        $baseEvent = new Event([
            'start_at' => '2026-03-01 08:00:00',
            'end_at' => '2026-03-01 09:00:00',
        ]);
        $repeatFrom = '2026-03-03';
        $repeatTo = '2026-03-03';
        $repeatDays = ['tuesday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertCount(1, $events);
        $this->assertEquals('2026-03-03 08:00:00', $events->first()->start_at);
        $this->assertEquals('2026-03-03 09:00:00', $events->first()->end_at);
    }

    public function test_generate_series_handles_events_spanning_across_midnight(): void
    {
        // Prepare
        // Evento que inicia a las 23:00 y finaliza a las 01:00 del día siguiente (120 minutos)
        $baseEvent = new Event([
            'start_at' => '2026-03-01 23:00:00',
            'end_at' => '2026-03-02 01:00:00',
        ]);
        $repeatFrom = '2026-03-02'; // Lunes
        $repeatTo = '2026-03-02';
        $repeatDays = ['monday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertCount(1, $events);
        $this->assertEquals('2026-03-02 23:00:00', $events->first()->start_at);
        $this->assertEquals('2026-03-03 01:00:00', $events->first()->end_at);
    }

    public function test_generate_series_handles_leap_year_dates_correctly(): void
    {
        // Prepare
        // 2028 es año bisiesto. 2028-02-29 es Martes
        $baseEvent = new Event([
            'start_at' => '2028-02-01 15:00:00',
            'end_at' => '2028-02-01 16:00:00',
        ]);
        $repeatFrom = '2028-02-28';
        $repeatTo = '2028-03-01';
        $repeatDays = ['tuesday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertCount(1, $events);
        $this->assertEquals('2028-02-29 15:00:00', $events->first()->start_at);
        $this->assertEquals('2028-02-29 16:00:00', $events->first()->end_at);
    }

    public function test_generate_series_returns_empty_collection_when_repeat_from_is_after_repeat_to(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatFrom = '2026-03-10';
        $repeatTo = '2026-03-01';
        $repeatDays = ['monday', 'tuesday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertTrue($events->isEmpty());
    }

    public function test_generate_series_preserves_base_event_model_attributes(): void
    {
        // Prepare
        $baseEvent = new Event([
            'title' => 'Sprint Planning',
            'description' => 'Bi-weekly team backlog refinement',
            'user_id' => 10,
            'event_category_id' => 3,
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-02';
        $repeatDays = ['monday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $cloned = $events->first();
        $this->assertEquals('Sprint Planning', $cloned->title);
        $this->assertEquals('Bi-weekly team backlog refinement', $cloned->description);
        $this->assertEquals(10, $cloned->user_id);
        $this->assertEquals(3, $cloned->event_category_id);
    }

    public function test_generate_series_throws_error_exception_when_invalid_day_name_is_provided(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-08';
        $repeatDays = ['funday'];
        // Assert
        $this->expectException(\ErrorException::class);
        // Execute
        $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
    }

    public function test_generate_series_does_not_duplicate_events_for_repeated_day_names(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-08';
        $repeatDays = ['monday', 'monday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertCount(1, $events);
    }

    public function test_generate_series_returns_event_per_day_when_all_days_selected(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-08';
        $allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $allDays);
        // Assert
        $this->assertCount(7, $events);
    }

    public function test_generate_series_does_not_mutate_base_event(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $originalStart = $baseEvent->start_at;
        $originalEnd = $baseEvent->end_at;
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-08';
        $repeatDays = ['monday'];
        // Execute
        $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertEquals($originalStart, $baseEvent->start_at);
        $this->assertEquals($originalEnd, $baseEvent->end_at);
        $this->assertNull($baseEvent->repeat_code);
    }

    public function test_generate_series_produces_different_repeat_code_across_calls(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 11:00:00',
        ]);
        $repeatDays = ['monday'];
        // Execute
        $eventsSeriesOne = $this->service->generateSeries($baseEvent, '2026-03-02', '2026-03-02', $repeatDays);
        $eventsSeriesTwo = $this->service->generateSeries($baseEvent, '2026-03-09', '2026-03-09', $repeatDays);
        // Assert
        $this->assertNotEquals(
            $eventsSeriesOne->first()->repeat_code,
            $eventsSeriesTwo->first()->repeat_code
        );
    }

    public function test_generate_series_handles_zero_duration_event(): void
    {
        // Prepare
        $baseEvent = new Event([
            'start_at' => '2026-03-01 10:00:00',
            'end_at' => '2026-03-01 10:00:00',
        ]);
        $repeatFrom = '2026-03-02';
        $repeatTo = '2026-03-02';
        $repeatDays = ['monday'];
        // Execute
        $events = $this->service->generateSeries($baseEvent, $repeatFrom, $repeatTo, $repeatDays);
        // Assert
        $this->assertCount(1, $events);
        $this->assertEquals($events->first()->start_at, $events->first()->end_at);
    }
}
