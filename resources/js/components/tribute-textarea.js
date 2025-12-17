export default function tributeTextareaEditor({
    statePath = null,
    triggers = ['@'],
    mentionableItems = [],
    lookupKey = 'value',
    fillKey = null,
    menuShowMinLength = 1,
    menuItemLimit = 10,
    enableDynamicSearch = false,
    getMentionResultUsing = null,
    rootRef = 'editor',
} = {}) {
    return {
        init() {
            const root = this.$refs?.[rootRef] || (statePath ? document.getElementById(`${statePath}-content`) : null) || this.$el;

            const attachTribute = () => {
                const target = root?.querySelector?.('[contenteditable="true"]') || root;
                if (!target || typeof Tribute === 'undefined') return false;

                const collections = (Array.isArray(triggers) ? triggers : [triggers]).map((t) => ({
                    trigger: t,
                    values: (text, cb) => {
                        if (enableDynamicSearch && typeof getMentionResultUsing === 'function') {
                            Promise.resolve(getMentionResultUsing(text, statePath)).then(cb);
                        } else {
                            const filtered = mentionableItems.filter((item) => {
                                const v = (item?.[lookupKey] ?? '').toString().toLowerCase();
                                return v.includes((text || '').toLowerCase());
                            });
                            cb(filtered);
                        }
                    },
                    lookup: lookupKey,
                    fillAttr: fillKey || lookupKey,
                    menuShowMinLength,
                    menuItemLimit,
                }));

                const tribute = new Tribute({ collection: collections });
                tribute.attach(target);

                target.addEventListener('tribute-replaced', () => {
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                });

                return true;
            };

            let tries = 0;
            const interval = setInterval(() => {
                tries++;
                if (attachTribute() || tries >= 20) clearInterval(interval);
            }, 150);
        },
    };
}
