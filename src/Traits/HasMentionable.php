<?php

namespace Codenzia\FilamentComments\Traits;

trait HasMentionable
{
    public function getMentionableItems(?string $searchKey): array
    {
        return resolve(config('codenzia-comments.mentionable.model'))
            ->query()
            ->where(config('codenzia-comments.mentionable.column.label'), 'like', "%$searchKey%")
            ->get()
            ->map(function ($mentionable) {
                return [
                    'id' => $id = $mentionable->{config('codenzia-comments.mentionable.column.id')},
                    'name' => $mentionable->{config('codenzia-comments.mentionable.column.label')},
                    'username' => $mentionable->{config('codenzia-comments.mentionable.column.label')},
                    'avatar' => $mentionable->{config('codenzia-comments.mentionable.column.avatar')},
                    'url' => url(str_replace('{id}', $id, config('codenzia-comments.mentionable.url'))),
                ];
            })
            ->toArray();
    }
}
