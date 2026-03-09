<?php

namespace Codenzia\FilamentComments\Enums;

enum ChannelType: string
{
    case Channel = 'channel';
    case DirectMessage = 'direct_message';

    public function label(): string
    {
        return match ($this) {
            self::Channel => __('Channel'),
            self::DirectMessage => __('Direct Message'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Channel => 'heroicon-o-hashtag',
            self::DirectMessage => 'heroicon-o-chat-bubble-left-right',
        };
    }
}
