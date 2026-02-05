<x-filament-panels::page>
    <div class="space-y-6">
        @if($record->description)
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $record->description }}
            </div>
        @endif

        <livewire:codenzia-comments::comments 
            :record="$record" 
            :activeChannelId="$record->id" 
        />
    </div>
</x-filament-panels::page>
