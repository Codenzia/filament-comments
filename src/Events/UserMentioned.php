<?php

namespace Codenzia\FilamentComments\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;

class UserMentioned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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
