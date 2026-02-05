<x-filament-panels::page>
    <div class="space-y-6">
        @if($channel?->description)
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $channel->description }}
            </div>
        @endif

        <livewire:codenzia-comments::comments
            :record="$channel"
            :activeChannelId="$channel?->id"
        />
    </div>
</x-filament-panels::page>
