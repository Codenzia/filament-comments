@include('filament-forms::components.rich-editor')
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tributejs/3.7.0/tribute.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tributejs/3.7.0/tribute.css" />
<script>
    (function() {
        const statePath = '{{ $getStatePath() }}';
        const mentionables = @json($getMentionables());

        function findEditor() {
            console.log('🔍 Searching for editor with state path:', statePath);

            // Try different selectors with detailed logging
            const selectors = [
                '[data-state-path="' + statePath + '"] .ProseMirror',
                '[data-state-path="' + statePath + '"] .tiptap.ProseMirror',
                '[data-state-path="' + statePath + '"] .tiptap',
                '[data-state-path="' + statePath + '"] [contenteditable="true"]',
                '.ProseMirror[contenteditable="true"]',
                '.tiptap.ProseMirror',
            ];

            for (let selector of selectors) {
                const editor = document.querySelector(selector);
                if (editor) {
                    console.log('✅ Found editor using selector:', selector);
                    return editor;
                }
                console.log('❌ No match for selector:', selector);
            }

            console.error('❌ Editor element not found with any selector');
            console.log('Available elements with data-state-path:', document.querySelectorAll('[data-state-path]'));
            console.log('Available contenteditable elements:', document.querySelectorAll('[contenteditable="true"]'));
            return null;
        }

        function attachTribute(editor) {
            // Check if tribute is already attached
            if (editor.tribute) {
                console.log('⚠️ Tribute already attached, skipping...');
                return;
            }

            // Set editor height
            const editorHeight = {{ config('codenzia-comments.editor.height', 100) }};
            editor.style.minHeight = editorHeight + 'px';

            const tribute = new Tribute({
                values: mentionables,
                selectTemplate: function(item) {
                    if (typeof item === "undefined") return null;
                    return '<a href="' + (item.original.link || '#') + '" class="tribute-mention" style="color: #f59e1b; font-weight: bold;">@' + item.original.key + '</a>';
                },
                menuItemTemplate: function(item) {
                    return `
                        <div style="display:flex;align-items:center;padding:8px 12px;">
                            <img src="${item.original.avatar}" style="width:32px;height:32px;border-radius:50%;margin-right:12px;object-fit:cover;vertical-align:middle;">
                            <div style="display:flex;flex-direction:column;">
                                <span style="font-weight:600;color:#f59e1b;">${item.original.key}</span>
                                <span style="font-size:14px;color:#bdbdbd;">@${item.original.value}</span>
                            </div>
                        </div>
                    `;
                },
                menuShowMinLength: 1
            });
            tribute.attach(editor);

            // Add custom highlight style only once
            if (!document.getElementById('tribute-custom-style')) {
                const style = document.createElement('style');
                style.id = 'tribute-custom-style';
                style.innerHTML = `
                    .tribute-container .highlight {
                        background: #f59e1b !important;
                        color: #fff !important;
                        border-radius: 12px !important;
                    }
                `;
                document.head.appendChild(style);
            }

            console.log('✅ Tribute attached successfully!');
        }

        function initializeTribute() {
            setTimeout(() => {
                const editor = findEditor();
                if (editor) {
                    attachTribute(editor);
                }
            }, 500);
        }

        // Use MutationObserver to watch for editor appearance
        function observeEditor() {
            const observer = new MutationObserver((mutations) => {
                const editor = findEditor();
                if (editor && !editor.tribute) {
                    console.log('🎯 Editor detected via MutationObserver');
                    attachTribute(editor);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Stop observing after 30 seconds
            setTimeout(() => observer.disconnect(), 30000);
        }

        // Initialize on various Livewire events
        document.addEventListener('livewire:initialized', () => {
            console.log('🚀 Livewire initialized');
            initializeTribute();
            observeEditor();
        });

        document.addEventListener('livewire:navigated', () => {
            console.log('🚀 Livewire navigated');
            initializeTribute();
        });

        // Also listen for Livewire component updates
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('commit', () => {
                initializeTribute();
            });
        }
    })();
</script>
@endpush
