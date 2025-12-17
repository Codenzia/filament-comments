<?php

namespace Codenzia\FilamentComments\Traits;

use Codenzia\FilamentComments\Dtos\MentionItem;
use Codenzia\FilamentComments\Dtos\TriggerConfig;
use Codenzia\FilamentComments\Helpers\Helper;
use Closure;
use Illuminate\Contracts\Support\Arrayable;

trait HasRichMentions
{
    /**
     * @var array<string, mixed>|Closure
     */
    protected array|Closure $mentionableItems = [];

    protected ?Closure $getMentionableItemsUsing = null;

    /**
     * @var string|array|null The trigger character(s) for mentions.
     */
    protected string|array|null $triggerWith = [];

    /**
     * @var string|null|Closure The key to pluck from mentionable items.
     */
    protected string|null|Closure $pluck = null;

    /**
     * @var string|null The avatar key for mentionable items.
     */
    protected ?string $avatar = null;

    /**
     * @var string The regex pattern for extracting mentions.
     */
    private const MENTION_PATTERN = '/\(--([a-zA-Z0-9_\.]+)--\)/';

    /**
     * @var int|null The minimum input length to show the mention menu.
     */
    protected ?int $menuShowMinLength = null;

    /**
     * @var string|null The lookup key for mentionable items.
     */
    protected ?string $lookupKey = null;

    /**
     * @var bool Whether to display the name of the mentionable item.
     */
    protected bool $displayName = true;

    /**
     * @var int|null The maximum number of items to show in the mention menu.
     */
    protected ?int $menuItemLimit = null;

    /**
     * @var array|null The configuration for specific triggers.
     */
    protected ?array $triggerConfigs = null;

    /**
     * @var string|null The prefix to add before the mention.
     */
    protected ?string $prefix = null;

    /**
     * @var string|null The suffix to add after the mention.
     */
    protected ?string $suffix = null;

    /**
     * @var string|null The field to use for the title in the mention dropdown.
     */
    protected ?string $labelKey = null;

    /**
     * @var string|null The field to use for the hint in the mention dropdown.
     */
    protected ?string $hintKey = null;

    public function getMentionableItemsUsing(Closure $callback): static
    {
        $this->getMentionableItemsUsing = $callback;

        return $this;
    }

    /**
     * @param  array<string, mixed>|Closure  $mentionsItems
     * @return $this
     *
     * @deprecated Use mentionableItems instead
     */
    public function mentionsItems(array|Closure $mentionsItems): static
    {
        $this->mentionableItems($mentionsItems);

        return $this;
    }

    /**
     * @param  array<string, mixed>|Closure  $mentionsItems
     * @return $this
     */
    public function mentionableItems(array|Closure $mentionsItems): static
    {
        $this->mentionableItems = $mentionsItems;

        return $this;
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getMentionableItems(): array
    {
        $this->mentionableItems = $this->evaluate($this->mentionableItems);

        if (blank($this->mentionableItems)) {
            $this->mentionableItems = $this->getMentionItemsUsingConfig();
        }

        /** @phpstan-ignore-next-line  */
        return collect($this->mentionableItems)
            ->map(fn ($item) => $item instanceof MentionItem ? $item->toArray() : $item)
            ->toArray();
    }

    public function setTriggerWith(array|string $triggers): static
    {
        $this->triggerWith = $triggers;
        return $this;
    }

    public function triggerWith(): array|string
    {
        return $this->triggerWith ?: config('codenzia-comments.default.trigger_with');
    }

    /**
     * @return $this
     *               The key must be included in the mentionsItems array
     */
    public function pluck(string|Closure $key): static
    {
        $this->pluck = $key;

        return $this;
    }

    public function getPluck(): ?string
    {
        return $this->evaluate($this->pluck);
    }

    public function avatar(string $key): static
    {
        $this->avatar = $key;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function getAvatar(): ?string
    {
        return $this->evaluate($this->avatar) ?? config('codenzia-comments.default.avatar');
    }

    public function menuShowMinLength(int $length = 2): self
    {
        $this->menuShowMinLength = $length;

        return $this;
    }

    public function getMenuShowMinLength(): int
    {
        return $this->menuShowMinLength ?? config('codenzia-comments.default.menu_show_min_length');
    }

    public function lookupKey(string $key): self
    {
        $this->lookupKey = $key;

        return $this;
    }

    public function getLookupKey(): ?string
    {
        return $this->lookupKey ?? config('codenzia-comments.default.lookup_key');
    }

    public function menuItemLimit(int $limit): self
    {
        $this->menuItemLimit = $limit;

        return $this;
    }

    public function getMenuItemLimit(): ?int
    {
        return $this->menuItemLimit ?? config('codenzia-comments.default.menu_item_limit');
    }

    /**
     * @return array<int, mixed>
     */
    public function getSearchResults(string $search): array
    {
        if (! $this->getMentionableItemsUsing) {
            return [];
        }

        $this->mentionableItems = $this->evaluate($this->getMentionableItemsUsing, [
            'query' => $search,
        ]);

        if ($this->mentionableItems instanceof Arrayable) {
            return $this->mentionableItems->toArray();
        }

        return $this->mentionableItems;
    }

    /**
     * @return array<string, mixed>
     */
    private function getMentionItemsUsingConfig(): array
    {
        return resolve(config('codenzia-comments.mentionable.model'))
            ->query()->get()->map(function ($item) {
                return (new MentionItem(
                    id: $item->{config('codenzia-comments.mentionable.column.id')},
                    label: $item->{config('codenzia-comments.mentionable.column.label')},
                    value: $item->{config('codenzia-comments.mentionable.column.value')},
                    avatar: $item->{config('codenzia-comments.mentionable.column.avatar')},
                    url: Helper::getResolvedUrl($item->{config('codenzia-comments.mentionable.column.id')}),
                ))->toArray();
            })->toArray();
    }

    public function getEnableDynamicSearch(): bool
    {
        return ! is_null($this->getMentionableItemsUsing);
    }

    /**
     * Set the configuration for specific triggers.
     *
     * @param  array|Closure  $configs  The configuration for specific triggers.
     * @return $this
     */
    public function triggerConfigs(array|Closure $configs): static
    {
        $this->triggerConfigs = $this->evaluate($configs);

        return $this;

    }

    /**
     * Get the configuration for specific triggers.
     *
     * @return array|null The configuration for specific triggers.
     */
    public function getTriggerConfigs(): ?array
    {
        $configs = $this->evaluate($this->triggerConfigs);
        if (is_null($configs)) {
            return config('codenzia-comments.default.trigger_configs') ?? [];
        }

        return collect($configs)
            ->mapWithKeys(fn ($config) => $config instanceof TriggerConfig
                ? [
                    $config->triggerChar => [
                        'lookupKey' => $config->lookupKey,
                        'prefix' => $config->prefix,
                        'suffix' => $config->suffix,
                        'labelKey' => $config->labelKey,
                        'hintKey' => $config->hintKey,
                    ],
                ]
                : throw new \Exception('Invalid trigger config, please use TriggerConfig DTO')
            )
            ->toArray();
    }

    /**
     * Set the prefix to add before the mention.
     *
     * @param  string  $prefix  The prefix to add.
     * @return $this
     */
    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get the prefix to add before the mention.
     *
     * @return string|null The prefix to add.
     */
    public function getPrefix(): ?string
    {
        return $this->prefix ?? config('codenzia-comments.default.prefix', '');
    }

    /**
     * Set the suffix to add after the mention.
     *
     * @param  string  $suffix  The suffix to add.
     * @return $this
     */
    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Get the suffix to add after the mention.
     *
     * @return string|null The suffix to add.
     */
    public function getSuffix(): ?string
    {
        return $this->suffix ?? config('codenzia-comments.default.suffix', '');
    }

    /**
     * Set the field to use for the title in the mention dropdown.
     *
     * @param  string  $field  The field to use for the title.
     * @return $this
     */
    public function labelKey(string $field): static
    {
        $this->labelKey = $field;

        return $this;
    }

    /**
     * Get the field to use for the title in the mention dropdown.
     *
     * @return string|null The field to use for the title.
     */
    public function getLabelKey(): ?string
    {
        return $this->labelKey ?? config('codenzia-comments.default.label_key', 'name');
    }

    /**
     * Set the field to use for the hint in the mention dropdown.
     *
     * @param  string  $field  The field to use for the hint.
     * @return $this
     */
    public function hintKey(string $field): static
    {
        $this->hintKey = $field;

        return $this;
    }

    /**
     * Get the field to use for the hint in the mention dropdown.
     *
     * @return string|null The field to use for the hint.
     */
    public function getHintKey(): ?string
    {
        return $this->hintKey ?? config('codenzia-comments.default.hint_key');
    }
}
