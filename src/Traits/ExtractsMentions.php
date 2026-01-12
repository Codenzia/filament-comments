<?php

namespace Codenzia\FilamentComments\Traits;

trait ExtractsMentions
{
    /**
     * Extract mentioned usernames from HTML comment text
     * Handles both HTML anchor tags (<a class="tribute-mention">@username</a>) and plain text (@username)
     */
    protected function extractMentions(string $commentHtml): array
    {
        $mentions = [];

        // Extract mentions from HTML anchor tags (tribute-mention format)
        preg_match_all('/<a[^>]*class="[^"]*tribute-mention[^"]*"[^>]*>@([^<]+)<\/a>/i', $commentHtml, $htmlMatches);
        if (! empty($htmlMatches[1])) {
            $mentions = array_merge($mentions, $htmlMatches[1]);
        }

        // Also extract plain text mentions as fallback
        preg_match_all('/@([\w.]+)/', strip_tags($commentHtml), $textMatches);
        if (! empty($textMatches[1])) {
            $mentions = array_merge($mentions, $textMatches[1]);
        }

        // Remove duplicates and trim
        return array_unique(array_map('trim', $mentions));
    }

    /**
     * Get the user model class name
     */
    protected function getUserModelClass(): string
    {
        if (config('codenzia-comments.user_model')) {
            return config('codenzia-comments.user_model');
        }

        return config('auth.providers.users.model', \App\Models\User::class);
    }
}
