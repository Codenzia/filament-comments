<?php

namespace Codenzia\FilamentComments\Forms;

use Filament\Forms\Components\Textarea;
use Closure;
use Filament\Forms\Components\RichEditor;
use Codenzia\FilamentComments\Traits\HasMentionable;
class TributeTextarea extends RichEditor
{
    use HasMentionable;
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
    public function urlPattern(string $pattern): static
    {
        $this->urlPattern = $pattern;
        return $this;
    }


}