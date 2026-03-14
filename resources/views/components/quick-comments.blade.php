@props([
    'record',
    'limit' => 3,
    'viewAllUrl' => null,
    'transparent' => false,
])

<livewire:filament-comments::quick-comments :record="$record" :limit="$limit" :view-all-url="$viewAllUrl" :transparent="$transparent" />
