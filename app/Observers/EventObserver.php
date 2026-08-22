<?php

namespace App\Observers;

use App\Models\Event;

class EventObserver
{
    public function created(Event $event): void
    {
        $event->participants()->attach($event->user_id, ['role' => 'admin', 'workflow_state' => 'confirmed']);
    }
}
