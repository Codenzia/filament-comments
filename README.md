# Filament Comments

A full-featured commenting system for Filament v4 with threaded replies, discussion channels, polls, events, emoji reactions, @mentions, and notifications — built with Livewire 3.

## Features

- **Threaded Replies** — Nested comment threads with expand/collapse
- **Discussion Channels** — Public and private channels with member management
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

- PHP 8.1+
- Laravel 11+
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
<x-codenzia-comments::comment :record="$record" />
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

Channels appear in the sidebar navigation automatically. Configure which roles can create channels:

```php
// config/codenzia-comments.php
'permissions' => [
    'create_channels_roles' => ['super_admin'],
],
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

### Reactions

Users can react to comments with emoji. One reaction per user per comment. Customize available reactions:

```php
// config/codenzia-comments.php
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

    // Navigation
    'navigation_group' => 'Channels',

    // Behavior
    'delete_replies_along_comments' => false,
    'enable_add_to_calendar' => true,

    // Editor
    'editor' => [
        'placeholder' => 'Type your comment here...',
        'height' => 100,
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
| `comment_channels` | Discussion channels with visibility, icon, project association |
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
- `members()` — Channel members
- `project()` — Associated project

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
