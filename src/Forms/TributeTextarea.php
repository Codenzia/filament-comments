<?php

namespace Codenzia\FilamentComments\Forms;

use Codenzia\FilamentComments\Traits\HasMentionable;
use Filament\Forms\Components\RichEditor;

class TributeTextarea extends RichEditor
{
    use HasMentionable;

    protected string $view = 'codenzia-comments::forms.components.tribute-textarea';

    protected array | \Closure $mentionables = [];

    protected array | \Closure $channelMentionables = [];

    protected array | \Closure $projectMentionables = [];

    protected array | \Closure $taskMentionables = [];

    protected string | \Closure $triggerWith = '@';

    protected string | \Closure $pluck = 'key';

    public function mentionables(array | \Closure $items): static
    {
        $this->mentionables = $items;

        return $this;
    }

    public function getMentionables(): array
    {
        return $this->evaluate($this->mentionables);
    }

    public function channelMentionables(array | \Closure $items): static
    {
        $this->channelMentionables = $items;

        return $this;
    }

    public function getChannelMentionables(): array
    {
        return $this->evaluate($this->channelMentionables);
    }

    public function projectMentionables(array | \Closure $items): static
    {
        $this->projectMentionables = $items;

        return $this;
    }

    public function getProjectMentionables(): array
    {
        return $this->evaluate($this->projectMentionables);
    }

    public function taskMentionables(array | \Closure $items): static
    {
        $this->taskMentionables = $items;

        return $this;
    }

    public function getTaskMentionables(): array
    {
        return $this->evaluate($this->taskMentionables);
    }

    public function triggerWith(string | \Closure $trigger): static
    {
        $this->triggerWith = $trigger;

        return $this;
    }

    public function getTriggerWith(): string
    {
        return $this->evaluate($this->triggerWith);
    }

    public function pluck(string | \Closure $pluck): static
    {
        $this->pluck = $pluck;

        return $this;
    }

    public function getPluck(): string
    {
        return $this->evaluate($this->pluck);
    }

    public function urlPattern(string $pattern): static
    {
        $this->urlPattern = $pattern;

        return $this;
    }
}
