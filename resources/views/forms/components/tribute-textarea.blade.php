<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('codenzia-filament-comment', 'codenzia/filament-comments') }}"
    x-data="tributeTextarea({
        mentionables: @js($getMentionables()),
        channelMentionables: @js($getChannelMentionables()),
        projectMentionables: @js($getProjectMentionables()),
        taskMentionables: @js($getTaskMentionables()),
        editorHeight: @js(config('codenzia-comments.editor.height', 100)),
        triggers: {
            mentionable: @js(config('codenzia-comments.mentionable.trigger', '@')),
            channel: @js(config('codenzia-comments.channel_mentionable.trigger', '#')),
            project: @js(config('codenzia-comments.project_mentionable.trigger', '$')),
            task: @js(config('codenzia-comments.task_mentionable.trigger', '%')),
        },
    })"
>
    @include('filament-forms::components.rich-editor')
</div>
