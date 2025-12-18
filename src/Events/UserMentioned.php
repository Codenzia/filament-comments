<?php

namespace Codenzia\FilamentComments\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\User;

class UserMentioned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mentionedUser;
    public $comment;
    public $byUser;

    public function __construct(User $mentionedUser, $comment, User $byUser)
    {
        $this->mentionedUser = $mentionedUser;
        $this->comment = $comment;
        $this->byUser = $byUser;
    }
}
