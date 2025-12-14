<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }">
        <x-filament-comments::editor
            x-model="state"
            :disabled="$isDisabled()"
            :placeholder="$getPlaceholder()"
            :rows="$getRows()"
            :attributes="\Filament\Support\prepare_inherited_attributes($getExtraInputAttributes())"
        />
    </div>
</x-dynamic-component>
