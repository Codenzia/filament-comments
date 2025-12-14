<?php

namespace Codenzia\FilamentComments\Forms\Components;

use Filament\Forms\Components\Field;

class Editor extends Field
{
    protected string $view = 'filament-comments::forms.components.editor';

    protected int $rows = 3;

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function getRows(): int
    {
        return $this->rows;
    }
}
