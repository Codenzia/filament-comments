@include('filament-forms::components.rich-editor')
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tributejs/3.7.0/tribute.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare. com/ajax/libs/tributejs/3.7.0/tribute.css" />
<script>
    document.addEventListener('livewire:initialized', () => {
        setTimeout(() => {

            // Try different selectors
            let editor = document.querySelector('[data-state-path="{{ $getStatePath() }}"] .ProseMirror');
            
            if (!editor) {
                editor = document.querySelector('.tiptap.ProseMirror');
            }
            
            if (!editor) {
                editor = document.querySelector('[data-state-path="{{ $getStatePath() }}"] .tiptap');
            }
            
            if (!editor) {
                // Last resort: find by contenteditable
                editor = document.querySelector('[data-state-path="{{ $getStatePath() }}"] [contenteditable="true"]');
            }
            
            console.log('Found editor:', editor);
            console.log('getMentionables:', @json($getMentionables()));
            
            if (editor) {
                const tribute = new Tribute({
                    values: @json($getMentionables()),
                    selectTemplate: function(item) {
                        console.log(item);
                        if (typeof item === "undefined") return null;
                        return '<a href="' + item.original.link + '">@' + item.original.key + '</a>';
                    },
                });
                tribute.attach(editor);
                console.log('✅ Tribute attached successfully! ');
            } else {
                console.error('❌ Editor element not found');
            }
        }, 1000); // Increased timeout
    });
</script>
@endpush