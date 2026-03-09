# Filament Comments

A full-featured commenting system for Filament v4 with threaded replies, discussion channels, polls, events, emoji reactions, @mentions, and notifications — built with Livewire 3.

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
- **Dark Mode** — Full dark mode support
- **Translations** — English and Arabic included

## Requirements

- PHP 8.3+
- Laravel 12+
- Filament 4.x
- Livewire 3.x

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

The package creates four tables:

| Table | Purpose |
|-------|---------|
| `comments` | Main comments with polymorphic relation, threading, type, approval status |
| `comment_channels` | Discussion channels and DMs with type, visibility, icon, project association |
| `comment_channel_members` | Channel membership pivot table |
| `comments_reactions` | Emoji reactions per user per comment |

## Models

### Comment
- `channel()` — Belongs to a channel
- `user()` — Comment author
- `parent()` — Parent comment (for threading)
- `replies()` — Child comments
- `reactions()` — Emoji reactions

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
| `HasComments` | Add to any model to enable commenting |
| `CanComment` | Add to user model for approval checks |
| `ExtractsMentions` | Parse HTML for tribute mentions |
| `HasMentionables` | Build mentionable lists from config |

## License

This package is dual-licensed:

- **MIT License** — Free for open source projects under an OSI-approved license.
- **Commercial License** — Required for proprietary/commercial projects. Visit [codenzia.com](https://codenzia.com) for details.

See [LICENSE.md](LICENSE.md) for full terms.
