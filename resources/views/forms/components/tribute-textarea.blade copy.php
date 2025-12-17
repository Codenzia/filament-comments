<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @once
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tributejs/5.1.3/tribute.css" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tributejs/5.1.3/tribute.min.js"></script>
        <style>
        .tribute-container ul { background: #1f2937 !important; } /* Dark gray for better visibility */
        .tribute-container li.highlight, .tribute-container li:hover { background: #374151 !important; }
        .tribute-container li { padding: 8px 15px; color: white; }
        .tribute-container { z-index: 10000 !important; } /* Keep existing Z-index fix */
        </style>
    @endonce

    {{-- ADD wire:ignore HERE --}}
    <textarea
        wire:ignore
        id="{{ $getId() }}"
        x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }"
        x-model="state"
        x-ref="textarea"
        class="block w-full transition duration-75 rounded-lg 
        shadow-sm disabled:opacity-70 border-gray-300 dark:border-gray-600 dark:bg-black dark:text-white"
        rows="5"
    ></textarea>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const elementId = "{{ $getId() }}";
                const element = document.getElementById(elementId);
                const users = @js($getMentionables());
                
                if (!element || !window.Tribute) return;
                const tribute = new Tribute({
                    values: users,
                });

                tribute.attach(element);

                element.addEventListener('tribute-replaced', function(e) {
                    element.dispatchEvent(new Event('input'));
                });
            }, 100);
        });
    </script>
</x-dynamic-component>