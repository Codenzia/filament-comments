<?php

namespace Codenzia\FilamentComments\Enums;

enum CommentType: string
{
    case Text = 'text';
    case Vote = 'vote';
    case Event = 'event';

    public function label(): string
    {
        return match ($this) {
            self::Text => __('filament-comments::messages.comment_types.text'),
            self::Vote => __('filament-comments::messages.comment_types.vote'),
            self::Event => __('filament-comments::messages.comment_types.event'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Text => 'heroicon-o-chat-bubble-left-ellipsis',
            self::Vote => 'heroicon-o-chart-bar',
            self::Event => 'heroicon-o-calendar-days',
        };
    }
}
