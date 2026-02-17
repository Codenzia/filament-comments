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

    public Comment $comment;

    public array $eventData;

    public function __construct(Comment $comment, array $eventData)
    {
        $this->comment = $comment;
        $this->eventData = $eventData;
    }
}
