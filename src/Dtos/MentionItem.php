<?php

namespace Codenzia\FilamentComments\Dtos;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class MentionItem implements Arrayable
{
    public function __construct(
        public int $id,
        public string $label,
        public string $value,
        public ?string $avatar,
        public ?string $url,
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->setMentionItem();
    }

    /**
     * @return array<string, mixed>
     */
    private function setMentionItem(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'value' => $this->value,
            'avatar' => $this->avatar,
            'url' => $this->url,
        ];
    }
}
