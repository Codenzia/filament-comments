@include('filament-forms::components.rich-editor')
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tributejs/3.7.0/tribute.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tributejs/3.7.0/tribute.css" />
<script>
    (function() {
        const statePath = '{{ $getStatePath() }}';
        const mentionables = @json($getMentionables());
        const channelMentionables = @json($getChannelMentionables());
        function findAllEditors() {
            return document.querySelectorAll('.ProseMirror[contenteditable="true"]');
        }

        function attachTribute(editor) {
            if (editor.tribute) {
                return;
            }

            if (typeof Tribute === 'undefined') {
                console.error('Tribute Debug: Tribute library is not loaded!');
                return;
            }

            // Set editor height
            const editorHeight = {{ config('codenzia-comments.editor.height', 100) }};
            editor.style.minHeight = editorHeight + 'px';

            let lastNonEmptySearch = '';

            const tribute = new Tribute({
                collection: [
                    {
                    trigger: '@',
                    lookup: 'key',
                    fillAttr: 'key',
                    allowSpaces: true,
                    requireLeadingSpace: false,
                    menuShowMinLength: 0,
                    values: function (text, cb) {
                        const search = (text || '').toLowerCase();
                        if (!search) {
                            if (lastNonEmptySearch.length > 0) {
                                return;
                            }
                            cb(mentionables);
                        } else {
                            lastNonEmptySearch = search;
                            const filtered = mentionables.filter(item =>
                                item.key.toLowerCase().includes(search)
                            );
                            cb(filtered);
                        }
                    },
                    selectTemplate: function(item) {
                        if (!item) return null;
                        lastNonEmptySearch = '';
                        return '@' + item.original.key + ' ';
                    },
                    menuItemTemplate: function(item) {
                        return `
                            <div style="display:flex;align-items:center;padding:8px 12px;">
                                <img src="${item.original.avatar}" style="width:32px;height:32px;border-radius:50%;margin-right:12px;object-fit:cover;vertical-align:middle;">
                                <div style="display:flex;flex-direction:column;">
                                    <span style="font-weight:600;color:#f59e1b;">${item.original.key}</span>
                                    <span style="font-size:14px;color:#bdbdbd;">@${item.original.key}</span>
                                </div>
                            </div>
                        `;
                    },
                },
                {
                        trigger: '#',
                        lookup: 'key',
                        fillAttr: 'key',
                        values: channelMentionables,
                        requireLeadingSpace: true,
                        selectTemplate: function(item) {
                            if (typeof item === "undefined") return null;
                            if (this.range.isContentEditable(this.current.element)) {
                                return (
                                    '<span contenteditable="false"><a href="' + (item.original.link || '#') + '" class="tribute-channel" style="color: #f59e1b; font-weight: bold;">#' +
                                    item.original.key +
                                    "</a></span>&nbsp;"
                                );
                            }
                            return "#" + item.original.key;
                        },
                        menuItemTemplate: function(item) {
                            return `
                                <div style="display:flex;align-items:center;padding:8px 12px;">
                                    <div style="display:flex;flex-direction:column;">
                                        <span style="font-weight:600;color:#f59e1b;"># ${item.original.key}</span>
                                    </div>
                                </div>
                            `;
                        },
                    }
            ]
            });

            tribute.attach(editor);
            editor.tribute = tribute;

            editor.addEventListener('tribute-replaced', function (e) {
                lastNonEmptySearch = '';
                tribute.hideMenu();

                // Filament v4 uses different event system
                const event = new Event('input', { bubbles: true });
                editor.dispatchEvent(event);

                // Also trigger change for Livewire
                const changeEvent = new Event('change', { bubbles: true });
                editor.dispatchEvent(changeEvent);
            });

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
                    .tribute-container {
                        z-index: 999999 !important;
                    }
                `;
                document.head.appendChild(style);
            }
        }

        function initializeAllTributes() {
            const editors = findAllEditors();
            editors.forEach(editor => {
                if (!editor.tribute) {
                    attachTribute(editor);
                }
            });
        }

        function observeEditors() {
            const observer = new MutationObserver((mutations) => {
                initializeAllTributes();
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeAllTributes();
            observeEditors();
        });

    })();
</script>
@endpush
