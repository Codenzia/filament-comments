@include('filament-forms::components.rich-editor')
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tributejs/3.7.0/tribute.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tributejs/3.7.0/tribute.css" />
<script>
    (function() {
        const statePath = '{{ $getStatePath() }}';
        const mentionables = @json($getMentionables());

        function findAllEditors() {
            // Find ALL contenteditable editors (including reply/edit forms)
            const editors = document.querySelectorAll('.ProseMirror[contenteditable="true"]');
            console.log('🔍 Found ' + editors.length + ' editor(s)');
            return editors;
        }

        function attachTribute(editor) {
            // Check if tribute is already attached
            if (editor.tribute) {
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

            console.log('✅ Tribute attached to editor');
        }

        function initializeAllTributes() {
            setTimeout(() => {
                const editors = findAllEditors();
                editors.forEach(editor => {
                    attachTribute(editor);
                });
            }, 500);
        }

        // Use MutationObserver to watch for ALL new editors
        function observeEditors() {
            const observer = new MutationObserver((mutations) => {
                // Find any new editors that don't have tribute yet
                const editors = findAllEditors();
                let attached = 0;
                editors.forEach(editor => {
                    if (!editor.tribute) {
                        attachTribute(editor);
                        attached++;
                    }
                });
                if (attached > 0) {
                    console.log('🎯 Attached tribute to ' + attached + ' new editor(s)');
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Don't disconnect - keep watching for new editors
            console.log('👀 Watching for new editors...');
        }

        // Initialize on various Livewire events
        document.addEventListener('livewire:initialized', () => {
            console.log('🚀 Livewire initialized');
            initializeAllTributes();
            observeEditors();
        });

        document.addEventListener('livewire:navigated', () => {
            console.log('🚀 Livewire navigated');
            initializeAllTributes();
        });

        // Also listen for Livewire component updates
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('commit', () => {
                setTimeout(() => {
                    initializeAllTributes();
                }, 200);
            });
        }
    })();
</script>
@endpush
