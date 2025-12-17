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
}<?php

namespace Codenzia\FilamentComments\Traits;

use Closure;

/**
 * Provides a simple API to pass and retrieve mentionable items.
 * Delegates to HasRichMentions under the hood.
 */
trait HasMentionables
{
    /**
     * Accept an array or Closure to provide mentionable items.
     * The structure should match MentionItem::toArray() or a compatible array.
     */
    public function mentionables(array|Closure $items): static
    {
        // Delegate to HasRichMentions::mentionableItems()
        if (method_exists($this, 'mentionableItems')) {
            $this->mentionableItems($items);
        }

        return $this;
    }

    /**
     * Retrieve the resolved mentionable items as array.
     */
    public function getMentionables(): array
    {
        // Delegate to HasRichMentions::getMentionableItems()
        if (method_exists($this, 'getMentionableItems')) {
            return $this->getMentionableItems();
        }

        return [];
    }
}
