<?php

namespace Codenzia\FilamentComments\Dtos;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class SimpleMentionItem implements Arrayable
{
    public function __construct(
        public ?int $id,
        public string $title,
        public string $value,
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'value' => $this->value,
        ];
    }
}
