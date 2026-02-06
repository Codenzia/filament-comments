<x-filament-panels::page>
    <div class="space-y-6">
        <livewire:codenzia-comments::comments
        :record="$channel"
        :activeChannelId="$channel?->id"
    />
    </div>
</x-filament-panels::page>
