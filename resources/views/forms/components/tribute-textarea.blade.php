<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('codenzia-filament-comment', 'codenzia/filament-comments') }}"
    x-data="tributeTextarea({
        mentionables: @js($getMentionables()),
        channelMentionables: @js($getChannelMentionables()),
        projectMentionables: @js($getProjectMentionables()),
        taskMentionables: @js($getTaskMentionables()),
        editorHeight: @js(config('filament-comments.editor.height', 100)),
        triggers: {
            mentionable: @js(config('filament-comments.mentionable.trigger', '@')),
            channel: @js(config('filament-comments.channel_mentionable.trigger', '#')),
            project: @js(config('filament-comments.project_mentionable.trigger', '$')),
            task: @js(config('filament-comments.task_mentionable.trigger', '%')),
        },
    })"
>
    @include('filament-forms::components.rich-editor')
</div>
