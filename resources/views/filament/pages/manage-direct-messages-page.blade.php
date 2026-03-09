<x-filament-panels::page>
    {{ $this->table }}

    @if ($this->shouldOpenCreateModal)
        <div x-init="$nextTick(() => $wire.mountTableAction('create'))" />
    @endif
</x-filament-panels::page>
