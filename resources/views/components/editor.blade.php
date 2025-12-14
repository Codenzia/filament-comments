@props([
    'disabled' => false,
    'placeholder' => null,
    'rows' => 3,
])

<textarea
    {{
        $attributes->class([
            'filament-comments-editor block w-full rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500',
        ])
    }}
    rows="{{ $rows }}"
    @if ($disabled) disabled @endif
    @if ($placeholder) placeholder="{{ $placeholder }}" @endif
></textarea>
