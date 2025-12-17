@props([
    'record',
    'mentionables' => [],
])

<div>
    <livewire:codenzia-comments::comments :record="$record" :mentionables="$mentionables" />
</div>
