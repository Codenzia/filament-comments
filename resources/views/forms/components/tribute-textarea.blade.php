<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('codenzia-filament-comment', 'codenzia/filament-comments') }}"
    x-data="tributeTextarea({
        mentionables: @js($getMentionables()),
        channelMentionables: @js($getChannelMentionables()),
        projectMentionables: @js($getProjectMentionables()),
        taskMentionables: @js($getTaskMentionables()),
        editorHeight: @js(config('codenzia-comments.editor.height', 100)),
    })"
>
    @include('filament-forms::components.rich-editor')
</div>
