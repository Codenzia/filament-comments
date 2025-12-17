<?php

namespace Codenzia\FilamentComments\Dtos;

use Illuminate\Contracts\Support\Arrayable;

readonly class TriggerConfig implements Arrayable
{
    public function __construct(
        public string $triggerChar,
        public string $lookupKey,
        public string $prefix,
        public string $suffix,
        public string $labelKey,
        public string $hintKey = '',

    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            $this->triggerChar => [
                'lookupKey' => $this->lookupKey,
                'prefix' => $this->prefix,
                'suffix' => $this->suffix,
                'labelKey' => $this->labelKey,
                'hintKey' => $this->hintKey,
            ],
        ];
    }
}
