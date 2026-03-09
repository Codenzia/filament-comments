@props([
    'record',
    'mentionables' => [],
])

<div>
    <livewire:filament-comments::comments :record="$record" :mentionables="$mentionables" />
</div>
