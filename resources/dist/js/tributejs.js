import Tribute from "tributejs";

function createTribute({
    statePath,
    triggerWith,
    pluck,
    menuShowMinLength,
    menuItemLimit,
    lookupKey,
    loadingItemString,
    noResultsString,
    valuesFunction,
    triggerConfigs = null,
    prefix = '',
    suffix = '',
    labelKey = 'id',
    hintKey = null
}) {

    const targetElement = document.getElementById(statePath);

    // Helper function to search across all object properties
    function searchInObject(item, searchText) {
        if (!searchText) return true;

        const searchLower = searchText.toLowerCase();

        // Search in all string properties of the object
        for (const key in item) {
            if (typeof item[key] === 'string') {
                const valueToSearch = item[key].toLowerCase();

                if (valueToSearch.includes(searchLower)) {
                    return true;
                }
            }
        }

        return false;
    }

    // Helper function to get display value based on lookupKey or default
    function getDisplayValue(item, currentLookupKey) {
        // If lookupKey is specified and exists in the item, use it
        if (currentLookupKey && item[currentLookupKey]) {
            return item[currentLookupKey];
        }

        // Otherwise, try common fields in order
        if (item.value) return item.value;
        if (item.label) return item.label;
        if (item.hint) return item.hint;

        // If none of the above, use the first string property
        for (const key in item) {
            if (typeof item[key] === 'string') {
                return item[key];
            }
        }

        // Last resort
        return "Unknown";
    }

    // Helper function to get label value
    function getLabel(item, field) {
        console.log('item:', item)
        console.log('field:', field)
        if (field && item[field]) {
            return item[field];
        }

        if (item.label) return item.label;
        if (item.hint) return item.hint;
        if (item.value) return item.value;

        return getDisplayValue(item, null);
    }

    // Helper function to get hint value
    function getHint(item, field, currentPrefix, currentSuffix, lookupKey) {
        if (field && item[field]) {
            return `${currentPrefix}${item[field]}${currentSuffix}`;
        }

        const displayValue = getDisplayValue(item, lookupKey);
        return `${currentPrefix}${displayValue}${currentSuffix}`;
    }

    // If triggerWith is an array (multiple triggers)
    const collections = [];

    triggerWith.forEach(trigger => {
        // Check if we have specific config for this trigger
        let currentLookupKey = lookupKey;
        let currentPrefix = prefix || '';
        let currentSuffix = suffix || '';
        let currentLabelKey = labelKey;
        let currentHintKey = hintKey;
        let currentConfig = null;

        if (triggerConfigs && triggerConfigs[trigger]) {
            currentConfig = triggerConfigs[trigger];

            if (currentConfig.lookupKey) {
                currentLookupKey = currentConfig.lookupKey;
            }

            if (currentConfig.prefix !== undefined) {
                currentPrefix = currentConfig.prefix;
            }

            if (currentConfig.suffix !== undefined) {
                currentSuffix = currentConfig.suffix;
            }

            // Support both camelCase and snake_case
            if (currentConfig.labelKey) {
                currentLabelKey = currentConfig.labelKey;

            } else if (currentConfig.label_key) {
                currentLabelKey = currentConfig.label_key;
            }

            // Support both camelCase and snake_case
            if (currentConfig.hintKey) {
                currentHintKey = currentConfig.hintKey;
            } else if (currentConfig.hint_key) {
                currentHintKey = currentConfig.hint_key;
            }
        }

        collections.push({
            trigger: trigger,
            values: function (text, cb) {
                // If we have a specific filter function for this trigger, use it
                if (currentConfig && typeof currentConfig.filter === 'function') {
                    return currentConfig.filter(text, cb, valuesFunction);
                }

                // Otherwise use the default filter
                valuesFunction(text, function (items) {
                    // Filter items by searching across all properties
                    const filteredItems = items.filter(item => searchInObject(item, text));

                    cb(filteredItems);
                });
            },
            lookup: function (item) {
                return getDisplayValue(item, currentLookupKey);
            },
            menuShowMinLength: menuShowMinLength,
            menuItemLimit: menuItemLimit,
            loadingItemTemplate: `<div class="loading-item">${loadingItemString}</div>`,
            menuContainer: document.body,
            menuItemTemplate: function (item) {
                const label = getLabel(item.original, currentLabelKey);
                const hint = getHint(item.original, currentHintKey, currentPrefix, currentSuffix, currentLookupKey);

                return `
                    <div class='mention-item'>
                        ${item.original.avatar ? `<img class="mention-item__avatar" src="${item.original.avatar}" alt="${label}"/>` : ''}
                        <div class='mention-item__info'>
                            <div class='mention-item__info-label'>${label}</div>
                            <div class='mention-item__info-hint'>${hint}</div>
                        </div>
                    </div>
                `;
            },
            selectTemplate: function (item) {
                if (typeof item === "undefined") return null;

                const displayValue = getDisplayValue(item.original, currentLookupKey);

                if (item.original.url !== null) {
                    return `<a href="${item.original.url}(--${item.original[pluck]}--)">${currentPrefix}${displayValue}${currentSuffix}</a>`;
                } else {
                    return `${currentPrefix}${displayValue}${currentSuffix}`;
                }
            },
            noMatchTemplate: function () {
                return `<span class="no-match">${noResultsString}</span>`;
            }
        });
    });

    const tribute = new Tribute({
        collection: collections
    });

    tribute.attach(targetElement);

    setupEventListeners(targetElement, tribute);

    return tribute;
}

function setupEventListeners(targetElement, tribute) {
    targetElement.addEventListener("tribute-active-true", function () {
        tribute.menu.classList.add('tribute-active');
    });

    targetElement.addEventListener("tribute-active-false", function () {
        tribute.menu.classList.remove('tribute-active');
    });

    targetElement.addEventListener("keydown", function (event) {
        if (!tribute.isActive) return;

        const activeItem = tribute.menu.querySelector(".highlight");
        if (!activeItem) return;

        if (event.key === "ArrowDown") {
            // Scroll down to the next item
            const nextItem = activeItem.nextElementSibling;
            if (nextItem) {
                nextItem.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
        } else if (event.key === "ArrowUp") {
            // Scroll up to the previous item
            const prevItem = activeItem.previousElementSibling;
            if (prevItem) {
                prevItem.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
        }
    });
}

export function editor()  {
    console.log('Nice to meet you!');
}

export function mention({
    statePath,
    mentionableItems,
    triggerWith,
    pluck,
    menuShowMinLength = 2,
    menuItemLimit = 10,
    lookupKey,
    loadingItemString,
    noResultsString,
    triggerConfigs = null,
    prefix = '',
    suffix = '',
    labelKey = 'label',
    hintKey = null,
    enableDynamicSearch,
    getMentionResultUsing
}) {
    return {
        statePath,
        pluck,
        menuShowMinLength,
        lookupKey,
        menuItemLimit,
        enableDynamicSearch,
        getMentionResultUsing,
        init() {
            createTribute({
                statePath: this.statePath,
                triggerWith,
                pluck,
                menuShowMinLength,
                lookupKey,
                menuItemLimit,
                loadingItemString,
                noResultsString,
                triggerConfigs,
                prefix,
                suffix,
                labelKey,
                hintKey,
                enableDynamicSearch,
                getMentionResultUsing,
                valuesFunction: function (text, cb) {
                    if (enableDynamicSearch) {
                        this.getMentionResultUsing(text, this.statePath).then((response) => {
                            cb(response);
                        });
                    } else {
                        const items = mentionableItems.filter(function (user) {
                            return user[lookupKey].toLowerCase().includes(text.toLowerCase())
                        }
                        );
                        cb(items);
                    }
                }
            });
        }
    };
}
