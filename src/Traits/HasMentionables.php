<?php

namespace Codenzia\FilamentComments\Traits;

trait HasMentionables
{
    protected function prepareMentionables(array $mentionables): array
    {
        return collect($mentionables)
            ->map(function ($item) {
                if (is_array($item)) {
                    return [
                        'key' => $item['name'] ?? $item['id'] ?? '',
                        'value' => $item['name'] ?? '',
                        'id' => $item['id'] ?? null,
                    ];
                }

                return [
                    'key' => $item->name ?? $item->id ?? '',
                    'value' => $item->name ?? '',
                    'id' => $item->id ?? null,
                ];
            })
            ->toArray();
    }
}
