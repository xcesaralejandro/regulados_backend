<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventSeries\StoreEventSeriesRequest;
use App\Http\Requests\EventSeries\UpdateEventSeriesRequest;
use App\Models\Event;
use App\Services\EventSchedulerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class EventSeriesController extends Controller
{

    public function store(StoreEventSeriesRequest $request, EventSchedulerService $schedulerService)
    {
        $event_template = new Event($request->validated());
        $event_template->user_id = Auth::id();
        $events = $schedulerService->generateSeries(
            $event_template,
            $request->validated('repeat_from'),
            $request->validated('repeat_to'),
            $request->validated('repeat_days')
        );
        DB::transaction(function () use ($events) {
            $events->each(fn(Event $event) => $event->save());
        });
        return response()->json($events, Response::HTTP_CREATED);
    }

    public function update(UpdateEventSeriesRequest $request, string $repeatCode)
    {
        $events = Event::where('repeat_code', $repeatCode)
            ->where('user_id', Auth::id())
            ->get();
        if ($events->isEmpty()) {
            return response()->json(null, Response::HTTP_FORBIDDEN);
        }
        $new_values = $request->validated();
        if ($request->has(['start_at', 'end_at'])) {
            DB::transaction(function () use ($events, $new_values) {
                foreach ($events as $event) {
                    $newStart = Carbon::parse($event->start_at)->setTimeFrom($new_values['start_at']);
                    $newEnd = Carbon::parse($event->end_at)->setTimeFrom($new_values['end_at']);
                    $event->update(array_merge($new_values, [
                        'start_at' => $newStart->toDateTimeString(),
                        'end_at'   => $newEnd->toDateTimeString(),
                    ]));
                }
            });
        } else {
            Event::where('repeat_code', $repeatCode)
                ->where('user_id', Auth::id())
                ->update($new_values);
        }
        $updatedSeries = Event::where('repeat_code', $repeatCode)->get();
        return response()->json($updatedSeries, Response::HTTP_OK);
    }

    public function destroy(string $repeatCode)
    {
        $query = Event::where('repeat_code', $repeatCode)->where('user_id', Auth::id());
        if ($query->count() === 0) {
            return response()->json(null, Response::HTTP_FORBIDDEN);
        }
        $query->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
