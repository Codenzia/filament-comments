<?php

namespace Codenzia\FilamentComments\Filament\Resources\CommentChannelResource\Pages;

use Codenzia\FilamentComments\Filament\Resources\CommentChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommentChannel extends EditRecord
{
    protected static string $resource = CommentChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn ($record) => $record->is_default),
        ];
    }
}
