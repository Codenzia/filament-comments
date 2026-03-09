<?php

namespace Codenzia\FilamentComments\Events;

use Codenzia\FilamentComments\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventAddedToCalendar
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $eventData
     */
    public function __construct(
        public Comment $comment,
        public array $eventData,
    ) {}
}
