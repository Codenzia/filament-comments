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
                        if (typeof item === "undefined") return null;
                        // Output a styled mention in the editor
                        return '<a href="' + (item.original.link || '#') + '" class="tribute-mention" style="color: #f59e1b; font-weight: bold;">@' + item.original.key + '</a>';
                    },
                    menuItemTemplate: function(item) {
                        // Custom dropdown item: avatar, name, username
                        const avatar = item.original.profile_photo_path ? '<img src="' + item.original.profile_photo_path + '" style="width:32px;height:32px;border-radius:50%;margin-right:12px;object-fit:cover;vertical-align:middle;">' : '';
                        return `
                            <div style="display:flex;align-items:center;padding:8px 12px;">
                                ${avatar}
                                <div style="display:flex;flex-direction:column;">
                                    <span style="font-weight:600;color:#f59e1b;">${item.original.key}</span>
                                    <span style="font-size:14px;color:#bdbdbd;">@${item.original.value}</span>
                                </div>
                            </div>
                        `;
                    },
                    // Optionally, set custom class for highlight
                    menuShowMinLength: 1
                });
                tribute.attach(editor);
                // Custom highlight style
                const style = document.createElement('style');
                style.innerHTML = `
                    .tribute-container .highlight {
                        background: #f59e1b !important;
                        color: #fff !important;
                        border-radius: 12px !important;
                    }
                `;
                document.head.appendChild(style);
                console.log('✅ Tribute attached successfully with custom template! ');
            } else {
                console.error('❌ Editor element not found');
            }
        }, 1000);
    });
</script>
@endpush