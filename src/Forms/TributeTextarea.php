<?php

namespace Codenzia\FilamentComments\Forms;

use Closure;
use Filament\Forms\Components\RichEditor;

class TributeTextarea extends RichEditor
{
    protected string $view = 'codenzia-comments::forms.components.tribute-textarea';

    protected array | \Closure $mentionables = [];

    public function mentionables(array | \Closure $items): static
    {
        $this->mentionables = $items;
        return $this;
    }

    public function getMentionables(): array
    {
        return $this->evaluate($this->mentionables);
    }

}