<?php

namespace Codenzia\FilamentComments\Enums;

enum CommentType: string
{
    case Text = 'text';
    case Vote = 'vote';
    case Event = 'event';
    case Meeting = 'meeting';
    case Todo = 'todo';
    case Survey = 'survey';
    case Risk = 'risk';

    public function label(): string
    {
        return match ($this) {
            self::Text => __('filament-comments::messages.comment_types.text'),
            self::Vote => __('filament-comments::messages.comment_types.vote'),
            self::Event => __('filament-comments::messages.comment_types.event'),
            self::Meeting => __('filament-comments::messages.comment_types.meeting'),
            self::Todo => __('filament-comments::messages.comment_types.todo'),
            self::Survey => __('filament-comments::messages.comment_types.survey'),
            self::Risk => __('filament-comments::messages.comment_types.risk'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Text => 'heroicon-o-chat-bubble-left-ellipsis',
            self::Vote => 'heroicon-o-chart-bar',
            self::Event => 'heroicon-o-calendar-days',
            self::Meeting => 'heroicon-o-video-camera',
            self::Todo => 'heroicon-o-clipboard-document-check',
            self::Survey => 'heroicon-o-clipboard-document-list',
            self::Risk => 'heroicon-o-exclamation-triangle',
        };
    }
}
