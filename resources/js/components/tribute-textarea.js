import Tribute from "tributejs";

export default function tributeTextarea({
    mentionables = [],
    channelMentionables = [],
    projectMentionables = [],
    taskMentionables = [],
    editorHeight = 100,
    triggers = {},
}) {
    const mentionTrigger = triggers.mentionable || '@';
    const channelTrigger = triggers.channel || '#';
    const projectTrigger = triggers.project || '$';
    const taskTrigger = triggers.task || '%';
    return {
        _tribute: null,
        _observer: null,
        _editor: null,

        init() {
            this.$nextTick(() => this._findAndAttach());
        },

        destroy() {
            if (this._observer) {
                this._observer.disconnect();
                this._observer = null;
            }
            if (this._tribute && this._editor) {
                try {
                    this._tribute.detach(this._editor);
                } catch (e) {}
            }
        },

        _findAndAttach() {
            const editor = this.$el.querySelector('.ProseMirror[contenteditable="true"]');
            if (editor) {
                this._attachTribute(editor);
                return;
            }

            // ProseMirror may not be ready yet — observe until it appears
            this._observer = new MutationObserver(() => {
                const ed = this.$el.querySelector('.ProseMirror[contenteditable="true"]');
                if (ed) {
                    this._observer.disconnect();
                    this._observer = null;
                    this._attachTribute(ed);
                }
            });
            this._observer.observe(this.$el, { childList: true, subtree: true });
        },

        _attachTribute(editor) {
            if (editor._tributeAttached) return;

            this._editor = editor;
            editor.style.minHeight = editorHeight + 'px';

            const collections = [];

            // user mentions
            if (mentionables.length > 0) {
                collections.push({
                    trigger: mentionTrigger,
                    lookup: 'key',
                    fillAttr: 'key',
                    allowSpaces: false,
                    requireLeadingSpace: false,
                    menuShowMinLength: 0,
                    menuContainer: document.body,
                    values: function (text, cb) {
                        const search = (text || '').toLowerCase();
                        if (!search) {
                            cb(mentionables);
                        } else {
                            cb(mentionables.filter(function (item) {
                                return item.key.toLowerCase().includes(search);
                            }));
                        }
                    },
                    selectTemplate: function (item) {
                        if (typeof item === 'undefined' || !item) return null;
                        const key = item.original.key;
                        const link = item.original.link || '#';
                        return '<span contenteditable="false"><a href="' + link + '" class="tribute-mention" style="color:#f59e1b;font-weight:bold;">' + mentionTrigger + key + '</a></span>\u00A0';
                    },
                    menuItemTemplate: function (item) {
                        var avatar = item.original.avatar
                            ? '<img src="' + item.original.avatar + '" class="mention-item__avatar" alt="' + item.original.key + '"/>'
                            : '';
                        return '<div class="mention-item">' +
                            avatar +
                            '<div class="mention-item__info">' +
                            '<div class="mention-item__info-label">' + item.original.key + '</div>' +
                            '<div class="mention-item__info-hint">' + mentionTrigger + item.original.key + '</div>' +
                            '</div></div>';
                    },
                    noMatchTemplate: function () {
                        return '<span class="no-match">No users found</span>';
                    },
                });
            }

            // channel mentions
            if (channelMentionables.length > 0) {
                collections.push({
                    trigger: channelTrigger,
                    lookup: 'key',
                    fillAttr: 'key',
                    allowSpaces: false,
                    requireLeadingSpace: true,
                    menuContainer: document.body,
                    values: channelMentionables,
                    selectTemplate: function (item) {
                        if (typeof item === 'undefined' || !item) return null;
                        var key = item.original.key;
                        var link = item.original.link || '#';
                        return '<span contenteditable="false"><a href="' + link + '" class="tribute-channel" style="color:#f59e1b;font-weight:bold;">' + channelTrigger + key + '</a></span>\u00A0';
                    },
                    menuItemTemplate: function (item) {
                        return '<div class="mention-item">' +
                            '<div class="mention-item__info">' +
                            '<div class="mention-item__info-label">' + channelTrigger + ' ' + item.original.key + '</div>' +
                            '</div></div>';
                    },
                    noMatchTemplate: function () {
                        return '<span class="no-match">No channels found</span>';
                    },
                });
            }

            // project mentions
            if (projectMentionables.length > 0) {
                collections.push({
                    trigger: projectTrigger,
                    lookup: 'key',
                    fillAttr: 'key',
                    allowSpaces: false,
                    requireLeadingSpace: true,
                    menuContainer: document.body,
                    values: projectMentionables,
                    selectTemplate: function (item) {
                        if (typeof item === 'undefined' || !item) return null;
                        var key = item.original.key;
                        var link = item.original.link || '#';
                        return '<span contenteditable="false"><a href="' + link + '" class="tribute-project" style="color:#10b981;font-weight:bold;">' + projectTrigger + key + '</a></span>\u00A0';
                    },
                    menuItemTemplate: function (item) {
                        return '<div class="mention-item">' +
                            '<div class="mention-item__info">' +
                            '<div class="mention-item__info-label">' + projectTrigger + ' ' + item.original.key + '</div>' +
                            '</div></div>';
                    },
                    noMatchTemplate: function () {
                        return '<span class="no-match">No projects found</span>';
                    },
                });
            }

            // task mentions
            if (taskMentionables.length > 0) {
                collections.push({
                    trigger: taskTrigger,
                    lookup: 'key',
                    fillAttr: 'key',
                    allowSpaces: false,
                    requireLeadingSpace: true,
                    menuContainer: document.body,
                    values: taskMentionables,
                    selectTemplate: function (item) {
                        if (typeof item === 'undefined' || !item) return null;
                        var key = item.original.key;
                        var link = item.original.link || '#';
                        return '<span contenteditable="false"><a href="' + link + '" class="tribute-task" style="color:#3b82f6;font-weight:bold;">' + taskTrigger + key + '</a></span>\u00A0';
                    },
                    menuItemTemplate: function (item) {
                        return '<div class="mention-item">' +
                            '<div class="mention-item__info">' +
                            '<div class="mention-item__info-label">' + taskTrigger + ' ' + item.original.key + '</div>' +
                            '</div></div>';
                    },
                    noMatchTemplate: function () {
                        return '<span class="no-match">No tasks found</span>';
                    },
                });
            }

            if (collections.length === 0) return;

            var tribute = new Tribute({ collection: collections });
            tribute.attach(editor);

            editor._tributeAttached = true;
            this._tribute = tribute;

            // Sync Tribute replacements with Livewire / ProseMirror
            editor.addEventListener('tribute-replaced', function () {
                editor.dispatchEvent(new Event('input', { bubbles: true }));
                editor.dispatchEvent(new Event('change', { bubbles: true }));
            });

            // Manage active class for CSS transitions
            editor.addEventListener('tribute-active-true', function () {
                if (tribute.menu) {
                    tribute.menu.classList.add('tribute-active');
                }
            });
            editor.addEventListener('tribute-active-false', function () {
                if (tribute.menu) {
                    tribute.menu.classList.remove('tribute-active');
                }
            });

            // Keyboard scroll support for the dropdown
            editor.addEventListener('keydown', function (event) {
                if (!tribute.isActive) return;
                var activeItem = tribute.menu ? tribute.menu.querySelector('.highlight') : null;
                if (!activeItem) return;
                if (event.key === 'ArrowDown') {
                    var next = activeItem.nextElementSibling;
                    if (next) next.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else if (event.key === 'ArrowUp') {
                    var prev = activeItem.previousElementSibling;
                    if (prev) prev.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        },
    };
}
