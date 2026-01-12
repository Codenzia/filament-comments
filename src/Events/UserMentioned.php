<?php

namespace Codenzia\FilamentComments\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMentioned
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Model $mentionedUser;

    public string $comment;

    public Model $byUser;

    public function __construct(Model $mentionedUser, string $comment, Model $byUser)
    {
        $this->mentionedUser = $mentionedUser;
        $this->comment = $comment;
        $this->byUser = $byUser;
    }
}
