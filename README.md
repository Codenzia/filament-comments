# Filament Comments — Threaded discussions, channels, DMs, polls & mentions

[![Latest Version](https://img.shields.io/packagist/v/codenzia/filament-comments.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-comments)
[![PHP Version](https://img.shields.io/packagist/php-v/codenzia/filament-comments.svg?style=flat-square)](https://packagist.org/packages/codenzia/filament-comments)
[![Filament](https://img.shields.io/badge/Filament-v4%20%7C%20v5-f59e0b?style=flat-square)](https://filamentphp.com)
[![Tests](https://img.shields.io/badge/tests-Pest%20v3-8b5cf6?style=flat-square)](https://pestphp.com)
[![License](https://img.shields.io/badge/license-MIT%20%7C%20Proprietary-blue?style=flat-square)](LICENSE.md)

A **full-featured commenting system for [Filament v4 and v5](https://filamentphp.com)** with threaded replies, discussion channels, Slack-style direct messages, polls, events with RSVP, emoji reactions, @mentions, notifications, watchlists, email digests, and a Tribute-powered rich-text composer — all built on Livewire 3.

> **Why this exists.** "Add comments" is one of those innocent-sounding requests that turns into a six-week project: threading, mentions, notifications, moderation, file uploads, link previews, watch/unwatch, DMs. This package ships all of that as a polymorphic drop-in — attach it to any model and you have a Slack-grade discussion surface without leaving Filament.

> **Try it live:** A working integration is included in the [Codenzia plugins demo](https://github.com/Codenzia/plugins-demo) at `/admin/demo/comments`.

---

## Features

- **Threaded Replies** — Nested comment threads with expand/collapse
- **Discussion Channels** — Public and private channels with member management
- **Direct Messages** — Slack-style 1-on-1 and group private conversations between users
- **Comment Types** — Text, polls (vote), and events with RSVP
- **Emoji Reactions** — Like, love, laugh, wow, sad, angry (customizable)
- **@Mentions** — Mention users, channels, projects, and tasks with configurable triggers
- **Rich Text Editor** — Tribute.js-powered textarea with mention autocomplete
- **File Uploads** — Images (up to 5MB) and documents (up to 10MB)
- **Comment Moderation** — Approval workflow with pending comments modal
- **Notifications** — Email and database notifications for mentions
- **Pin Comments** — Pin an important comment to the top of a discussion
- **Resolve Threads** — Mark comment threads as resolved (collapse by default)
- **Bookmarks** — Save comments for personal quick reference
- **Link to Tasks** — Reference a task from a project discussion comment
- **Watch/Unwatch** — Subscribe to all comments on a model, not just @mentions
- **Email Digest** — Optional daily summary of unread comments
- **Code Syntax Highlighting** — Automatic syntax highlighting for code blocks via highlight.js
- **Checklists** — Interactive checklist items within comments (`[ ]` / `[x]`)
- **Link Previews** — Auto-generated Open Graph cards for URLs in comments
- **Quick Comments** — Lightweight comment preview + composer for modals and cards (optimistic UI)
- **Dark Mode** — Full dark mode support
- **Translations** — English and Arabic included

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.3` |
| Laravel | `^12.0` |
| Filament | `^4.0 \|\| ^5.0` |
| Livewire | `^3.0` |

## Installation

Install via Composer:

```bash
composer require codenzia/filament-comments
```

Run the install command:

```bash
php artisan filament-comments:install
```

This publishes the config file and migrations. Run migrations:

```bash
php artisan migrate
```

### Filament Shield / role-based moderation (optional)

The comment permissions listed in `config/filament-comments.php` are seeded into
the standard Spatie `permissions` table — so if your app uses
[bezhansalleh/filament-shield](https://github.com/bezhanSalleh/filament-shield)
(which transitively requires `spatie/laravel-permission`), they show up in your
Shield UI automatically. No extra wiring.

If you don't use Shield or Spatie, the install command skips the permission
seeding step quietly — comments, channels, mentions, and DMs all work without
it. Add `spatie/laravel-permission` to your project later and re-run
`php artisan filament-comments:install` to opt in.

### Tailwind v4 Custom Theme

If your Filament panel uses a custom theme (Tailwind CSS v4), add the package's source paths so that utility classes are compiled:

```css
/* resources/css/filament/{panel}/theme.css */
@source '../../../../vendor/codenzia/*/src/**/*.php';
@source '../../../../vendor/codenzia/*/resources/views/**/*.blade.php';
```

This wildcard pattern covers all Codenzia packages at once.

Then rebuild your assets (`npm run build`).

## Setup

Register the plugin in your panel provider:

```php
use Codenzia\FilamentComments\FilamentCommentsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentCommentsPlugin::make(),
        ]);
}
```

## Usage

### Adding Comments to a Model

Add the `HasComments` trait to any model:

```php
use Codenzia\FilamentComments\Traits\HasComments;

class Post extends Model
{
    use HasComments;
}
```

Then use the Blade component in your views:

```blade
<x-filament-comments::comment :record="$record" />
```

Or add comments programmatically:

```php
$post->comment('Great article!');
$post->commentAsUser($user, 'Thanks for sharing.');
```

### Discussion Channels

The plugin automatically registers two Filament pages:

- **Manage Channels** — Create, edit, and manage discussion channels
- **Discussion Page** — View and interact with channel comments

Channels appear in the sidebar navigation automatically.

### Direct Messages

The plugin supports Slack-style direct messages — both 1-on-1 and group conversations. DMs appear in their own collapsible sidebar group.

The plugin registers a **Direct Messages** page where users can:
- View all their active conversations
- Start a new conversation with one or more users (group DMs)
- Click into a conversation to chat
- Remove themselves from a conversation

Sidebar shortcuts ("+ New Channel" / "+ New Message") auto-open the create modal for quick access. These are permission-aware and hidden when the user lacks the required permission.

#### Starting a DM Programmatically

```php
use Codenzia\FilamentComments\Models\CommentChannel;

// 1-on-1 DM (legacy two-argument syntax still works)
$dm = CommentChannel::findOrCreateDirectMessage($userId1, $userId2);

// Group DM — pass an array of user IDs
$dm = CommentChannel::findOrCreateDirectMessage([$userId1, $userId2, $userId3]);
```

This is safe to call multiple times — it returns the existing DM with the exact same set of members if one already exists.

#### Sidebar MRU Limits

Control how many channels and DMs appear in the sidebar to prevent it from becoming too tall:

```php
// config/filament-comments.php
'sidebar_limit' => [
    'channels' => 5,        // show 5 most recent channels
    'direct_messages' => 5,  // show 5 most recent DMs
],
```

Set to `null` to show all items. The "All" link always appears for accessing the full list.

#### Navigation Groups

Channels and Direct Messages each get their own collapsible sidebar group. Customize the labels:

```php
// config/filament-comments.php
'navigation_groups' => [
    'channels' => 'Channels',
    'direct_messages' => 'Direct Messages',
],
```

### Permissions

The plugin uses **Spatie Permission** for authorization and works seamlessly with **Filament Shield**.

#### Configuration

Each action maps to a Spatie permission name in `config/filament-comments.php`:

```php
'permissions' => [
    // Channels
    'create_channel' => 'create_comment_channel',
    'update_channel' => 'update_comment_channel',
    'delete_channel' => 'delete_comment_channel',
    'view_channel'   => 'view_comment_channel',

    // Direct Messages
    'view_direct_message'      => null, // null = any authenticated user
    'create_direct_message'    => null,
    'delete_direct_message'    => null,
    'add_member_direct_message' => null,
],
```

Set any permission to `null` to allow all authenticated users. Set to a string to require that Spatie permission. Pages return 403 when the user lacks the `view_*` permission.

```php
'permissions' => [
    'create_channel' => null, // any authenticated user can create channels
    'update_channel' => 'update_comment_channel',
    'delete_channel' => 'delete_comment_channel',
    'view_channel'   => null,
    'view_direct_message'      => null,
    'create_direct_message'    => null,
    'delete_direct_message'    => null,
    'add_member_direct_message' => null,
],
```

#### Seeding Permissions

The install command (`filament-comments:install`) can seed these permissions for you. You can also call it programmatically in your seeders:

```php
use Codenzia\FilamentComments\Commands\InstallCommand;

InstallCommand::seedPermissions();
```

This is safe to call multiple times — it uses `firstOrCreate`.

#### Using with Filament Shield

Shield auto-generates page-level permissions (e.g. `View:ManageChannelsPage`) but does **not** discover action-level permissions like `create_comment_channel`. You need to seed these separately via the install command or your seeder, then assign them to roles in Shield's role editor.

```php
// Example: grant channel management to a "moderator" role
$role = Role::findByName('moderator');
$role->givePermissionTo([
    'create_comment_channel',
    'update_comment_channel',
    'delete_comment_channel',
    'view_comment_channel',
]);
```

#### Authorization Logic

- **Channel owners** can always edit/delete their own channels
- **Permission holders** can manage any channel they have permission for
- The `super_admin` role bypasses all checks (standard Shield convention)

#### Checking Permissions Programmatically

```php
use Codenzia\FilamentComments\Filament\Pages\ManageChannelsPage;

if (ManageChannelsPage::can('create_channel')) {
    // user has the create_comment_channel permission
}
```

### Comment Types

#### Text Comments
Standard rich text comments with mention support.

#### Poll Comments
Create polls with a question and multiple options. Users vote directly in the comment thread.

#### Event Comments
Schedule events with title, date/time, and description. Users can RSVP with Going, Maybe, or Not Going.

### Mention System

Configure mention triggers for different entity types:

| Trigger | Entity | Config Key |
|---------|--------|------------|
| `@` | Users | `mentionable` |
| `#` | Channels | `channel_mentionable` |
| `$` | Projects | `project_mentionable` |
| `%` | Tasks | `task_mentionable` |

### Comment Moderation

By default, new comments are auto-approved. To require manual moderation:

```php
// config/filament-comments.php
'auto_approve' => false,
```

When `false`, new comments will have `is_approved = false` and won't appear until approved.

### Composer Appearance

Customize the composer background color to match your app's theme:

```php
// config/filament-comments.php
'composer' => [
    'bg' => '#ffffff',          // light mode
    'dark_bg' => '#16181C',     // dark mode
    'show_settings' => false,   // show a settings cog with color picker
],
```

Accepts any valid CSS color value (hex, rgb, hsl, etc.).

When `show_settings` is `true`, a cog icon appears in the composer toolbar allowing users to pick from preset background colors. The choice is saved in `localStorage`.

### Reactions

Users can react to comments with emoji. One reaction per user per comment. Customize available reactions:

```php
// config/filament-comments.php
'reactions' => [
    'like' => '👍',
    'love' => '❤️',
    'laugh' => '😄',
    'wow' => '😮',
    'sad' => '😢',
    'angry' => '😠',
],
```

### Quick Comments (Lightweight Preview + Composer)

A lightweight, embeddable comment preview with a quick reply composer — designed for modals, sidebars, and cards where the full `CommentsComponent` would be too heavy or cause Livewire nesting issues.

```blade
<x-filament-comments::quick-comments
    :record="$task"
    :limit="3"
    :view-all-url="url('app/task-details', $task->id)" />
```

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `record` | `Model` | required | Any model using `HasComments` |
| `limit` | `int` | `3` | Number of recent comments to show |
| `viewAllUrl` | `?string` | `null` | URL for the "View all" link |

Features:
- Shows the latest N comments with avatars, names, and timestamps
- Quick reply textarea with Enter-to-send
- **Alpine optimistic UI** — new comments appear instantly without Livewire re-render (modal-safe)
- Works inside Filament action modals without closing them
- Accepts any model with `HasComments` (tasks, projects, invoices, etc.)

Or use the Livewire component directly:

```blade
<livewire:filament-comments::quick-comments :record="$task" :limit="3" :view-all-url="$url" />
```

### Pinning Comments

Pin an important comment to the top of a discussion. Only one pinned comment per commentable — pinning a new one automatically unpins the previous.

- Pin/unpin via the action menu (map pin icon) on any root comment
- Pinned comment renders at top with an amber highlight border
- Available to all users who can post in the channel

```php
// Programmatic usage
$comment->pin();
$comment->unpin();

// Query pinned comments
Comment::pinned()->get();
```

### Resolving Threads

Mark root comment threads as resolved to keep discussions clean. Resolved threads collapse by default and show a green "Resolved by {user}" badge.

- Only root comments (not replies) can be resolved
- A "Show resolved" toggle in the header lets users show/hide resolved threads
- Resolved threads are hidden by default

```php
// Programmatic usage
$comment->resolve();        // resolves as current user
$comment->resolve($userId); // resolves as specific user
$comment->unresolve();

// Query scopes
Comment::resolved()->get();
Comment::unresolved()->get();
```

### Bookmarks

Personal bookmarks let users save comments for quick reference. Bookmarks are private — not visible to other users.

- Bookmark icon in the action menu (filled when bookmarked)
- Toggle on/off per comment

```php
// Check if bookmarked
$comment->isBookmarkedBy();       // current user
$comment->isBookmarkedBy($userId); // specific user
```

### Watching Discussions

Users can watch any commentable model to get notified on ALL new comments, not just @mentions. A bell icon toggles watch state.

```php
// On any model using HasComments
$task->toggleWatch();         // toggle for current user
$task->isWatchedBy();         // check if current user is watching
$task->isWatchedBy($userId);  // check specific user
$task->commentWatchers();     // MorphMany relationship
```

### Link Comments to Tasks

When commenting in a project discussion, users can link a comment to a specific task. The comment displays a small task reference card that links to the task detail page.

Configure the task model in config:

```php
// config/filament-comments.php
'task_mentionable' => [
    'model' => \App\Models\Task::class,
    'column' => ['id' => 'id', 'label' => 'title'],
    'url' => 'admin/tasks/{id}',
],
```

```php
// Relationship on Comment model
$comment->linkedTask; // BelongsTo relationship
```

### Email Digest

Optional daily email digest of unread comments for watchers. Disabled by default.

```php
// config/filament-comments.php
'digest' => [
    'enabled' => false,
    'schedule' => 'daily',
    'time' => '09:00',
],
```

Register the command in your scheduler:

```php
// bootstrap/app.php or app/Console/Kernel.php
$schedule->command('filament-comments:send-digest')->dailyAt('09:00');
```

The digest groups unread comments by source (task, project, channel) and only sends if there are new items in the last 24 hours.

### Code Syntax Highlighting

Code blocks in comments (`<pre><code>`) are automatically syntax-highlighted using [highlight.js](https://highlightjs.org/). Supports PHP, JavaScript, Python, SQL, HTML, CSS, Go, Java, JSON, YAML, XML, C++, Markdown, and more.

```php
// config/filament-comments.php
'code_highlighting' => true, // enabled by default
```

Highlighting is applied on initial render and after Livewire updates. Uses the `github-dark` theme by default.

### Checklists

Comments support interactive checklists using the `[ ]` / `[x]` syntax. Checklist items render as clickable checkboxes that toggle their state via Livewire.

Write in your comment:
```
- [ ] Review the PR
- [x] Write tests
- [ ] Deploy to staging
```

Clicking a checkbox updates the comment body in the database. Available to the comment author and other project members.

### Link Previews

URLs in comments are automatically enriched with Open Graph metadata cards showing title, description, image thumbnail, and domain.

```php
// config/filament-comments.php
'link_previews' => [
    'enabled' => true,
    'cache_ttl' => 3600, // cache previews for 1 hour
],
```

- Previews are fetched server-side on comment save
- Stored as JSON in the `link_previews` column
- Up to 3 link previews per comment
- Image URLs are excluded (only page URLs are previewed)
- Cached to avoid repeated fetches

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="filament-comments-config"
```

Key configuration options:

```php
return [
    // Models
    'comment_class' => \Codenzia\FilamentComments\Models\Comment::class,
    'user_model' => null, // defaults to auth config
    'project_model' => \App\Models\Project::class,
    'event_model' => null, // optional: persist events to a model

    // Table names
    'table_name' => 'comments',
    'reactions_table_name' => 'comments_reactions',
    'channels_table_name' => 'comment_channels',
    'channel_members_table_name' => 'comment_channel_members',

    // Navigation groups
    'navigation_groups' => [
        'channels' => 'Channels',
        'direct_messages' => 'Direct Messages',
    ],

    // Behavior
    'auto_approve' => true, // set false to require moderation
    'delete_replies_along_comments' => false,
    'enable_add_to_calendar' => true,

    // Editor
    'editor' => [
        'placeholder' => 'Type your comment here...',
        'height' => 100,
    ],

    // Composer appearance
    'composer' => [
        'bg' => '#ffffff',          // light mode background
        'dark_bg' => '#16181C',     // dark mode background
        'show_settings' => false,   // settings cog with color picker
    ],

    // Mentions
    'mentionable' => [
        'model' => \App\Models\User::class,
        'trigger' => '@',
        'column' => [
            'id' => 'id',
            'label' => 'name',
            'value' => 'name',
            'email' => 'email',
            'avatar' => 'profile_photo_path',
        ],
        'url' => 'admin/users/{id}',
    ],
];
```

## Database Schema

The package creates seven tables:

| Table | Purpose |
|-------|---------|
| `comments` | Main comments with polymorphic relation, threading, type, approval, pin, resolve, link previews |
| `comment_channels` | Discussion channels and DMs with type, visibility, icon, project association |
| `comment_channel_members` | Channel membership pivot table |
| `comment_channel_reads` | Read tracking per channel per user |
| `comments_reactions` | Emoji reactions per user per comment |
| `comment_bookmarks` | Personal bookmarks per user per comment |
| `comment_watches` | Polymorphic watch subscriptions per user per model |

## Models

### Comment
- `channel()` — Belongs to a channel
- `commentator()` — Comment author
- `parent()` — Parent comment (for threading)
- `replies()` — Child comments
- `reactions()` — Emoji reactions
- `bookmarks()` — User bookmarks
- `resolvedBy()` — User who resolved the thread
- `linkedTask()` — Linked task (configurable model)
- `pin()` / `unpin()` — Pin/unpin this comment
- `resolve()` / `unresolve()` — Resolve/unresolve this thread
- `isBookmarkedBy()` — Check if bookmarked by a user
- `scopePinned()` / `scopeResolved()` / `scopeUnresolved()` — Query scopes

### CommentBookmark
- `comment()` — The bookmarked comment
- `user()` — The user who bookmarked

### CommentWatch
- `watchable()` — Polymorphic relation to the watched model
- `user()` — The watching user

### CommentChannel
- `comments()` — All channel comments
- `members()` — Channel members (from project if linked, otherwise pivot table)
- `channelMembers()` — Direct channel members (always from pivot table)
- `createdBy()` — User who created the channel
- `project()` — Associated project
- `scopeChannels()` — Filter to only channels
- `scopeDirectMessages()` — Filter to only DMs
- `isDirectMessage()` / `isChannel()` — Type checks
- `findOrCreateDirectMessage(int|array $userIds)` — Find or create a DM between users (supports 1-on-1 and group DMs)
- `dmDisplayName()` — Display name showing other participants (e.g. "Alice, Bob +2")
- `dmAvatarUrl()` — Avatar URL of the other participant (1-on-1 DMs)

## Events

| Event | Dispatched When |
|-------|----------------|
| `CommentAdded` | New comment created |
| `CommentDeleted` | Comment removed |
| `UserMentioned` | User mentioned in a comment |
| `EventAddedToCalendar` | Event comment added to calendar |

## Traits

| Trait | Purpose |
|-------|---------|
| `HasComments` | Add to any model to enable commenting + watching |
| `CanComment` | Add to user model for approval checks |
| `ExtractsMentions` | Parse HTML for tribute mentions |
| `HasMentionables` | Build mentionable lists from config |

`HasComments` now includes watch/unwatch support:
- `commentWatchers()` — MorphMany of watchers
- `isWatchedBy($userId)` — Check if a user is watching
- `toggleWatch($userId)` — Toggle watch on/off, returns boolean

## License

This package is dual-licensed:

- **MIT License** — Free for open source projects under an OSI-approved license.
- **Commercial License** — Required for proprietary/commercial projects. Visit [codenzia.com](https://codenzia.com) for details.

See [LICENSE.md](LICENSE.md) for full terms.
