<?php

namespace Codenzia\FilamentComments\Traits;

trait HasMentionable
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMentionableItems(?string $searchKey): array
    {
        $columns = config('filament-comments.mentionable.column');
        $idColumn = $columns['id'] ?? 'id';
        $labelColumn = $columns['label'] ?? 'name';
        $avatarColumn = $columns['avatar'] ?? 'avatar';
        $urlPattern = config('filament-comments.mentionable.url', 'admin/users/{id}');

        return resolve(config('filament-comments.mentionable.model'))
            ->query()
            ->where($labelColumn, 'like', "%$searchKey%")
            ->get()
            ->map(function ($mentionable) use ($idColumn, $labelColumn, $avatarColumn, $urlPattern): array {
                $id = $mentionable->{$idColumn};

                return [
                    'id' => $id,
                    'name' => $mentionable->{$labelColumn},
                    'username' => $mentionable->{$labelColumn},
                    'avatar' => $mentionable->{$avatarColumn},
                    'url' => url(str_replace('{id}', $id, $urlPattern)),
                ];
            })
            ->toArray();
    }
}
