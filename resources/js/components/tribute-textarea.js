// export default function tributeTextarea({
//     state,
//     mentionables = [],
//     lookupKey = 'name',
//     fillKey = 'username',
// }) {
//     return {
//         state: state,
//         
//         init() {
//             // Wait for next tick to ensure DOM is ready
//             this.$nextTick(() => {
//                 if (this.$refs.input && typeof Tribute !== 'undefined') {
//                     this.initTribute();
//                 }
//             });
//         },
//         
//         initTribute() {
//             // Initialize Tribute
//             const tribute = new Tribute({
//                 values: mentionables,
//                 lookup: lookupKey,
//                 fillAttr: fillKey,
//                 
//                 // Customize the menu item UI
//                 menuItemTemplate: function (item) {
//                     const avatar = item.original.avatar_url || item.original.avatar;
//                     const name = item.original[lookupKey];
//                     return `
//                         <div class="flex items-center gap-2 py-1">
//                             ${avatar ? 
//                                 `<img src="${avatar}" class="w-6 h-6 rounded-full" alt="${name}" />` :
//                                 `<div class="w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-xs font-medium">
//                                     ${name.substring(0, 2).toUpperCase()}
//                                 </div>`
//                             }
//                             <span class="text-sm">${name}</span>
//                         </div>
//                     `;
//                 },
//                 
//                 // Customize what is inserted
//                 selectTemplate: function (item) {
//                     return '@' + item.original[fillKey];
//                 },
//                 
//                 noMatchTemplate: function() {
//                     return '<span class="text-xs text-gray-500 dark:text-gray-400 px-3 py-2">No users found</span>';
//                 },
//                 
//                 containerClass: 'tribute-container',
//                 itemClass: 'tribute-item',
//                 selectClass: 'tribute-active',
//                 menuShowMinLength: 1,
//                 positionMenu: true
//             });
// 
//             // Attach to the textarea
//             tribute.attach(this.$refs.input);
// 
//             // Update Livewire when Tribute inserts a mention
//             this.$refs.input.addEventListener('tribute-replaced', (e) => {
//                 this.state = e.target.value;
//                 // Dispatch input to ensure other scripts detect the change
//                 this.$refs.input.dispatchEvent(new Event('input'));
//             });
//         }
//     }
// }
