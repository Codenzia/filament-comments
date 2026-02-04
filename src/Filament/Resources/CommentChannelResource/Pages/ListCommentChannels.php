<?php

namespace Codenzia\FilamentComments\Filament\Resources\CommentChannelResource\Pages;

use Codenzia\FilamentComments\Filament\Resources\CommentChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommentChannels extends ListRecords
{
    protected static string $resource = CommentChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
