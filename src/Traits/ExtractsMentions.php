<?php

namespace Codenzia\FilamentComments\Traits;

trait ExtractsMentions
{
    /**
     * Extract mentioned usernames from HTML comment text
     * Only extracts from elements with tribute-mention class
     */
    protected function extractMentions(string $commentHtml): array
    {
        $mentions = [];

        // Use DOMDocument to parse HTML and extract only from tribute-mention elements
        $dom = new \DOMDocument();

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);

        // Load HTML - wrap in a container to handle fragments
        $dom->loadHTML('<?xml encoding="UTF-8">' . $commentHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Create XPath to find elements with tribute-mention class
        $xpath = new \DOMXPath($dom);

        // Find all anchor tags with tribute-mention class
        $nodes = $xpath->query('//a[contains(@class, "tribute-mention")]');

        if ($nodes && $nodes->length > 0) {
            foreach ($nodes as $node) {
                $textContent = trim($node->textContent);
                // Remove @ symbol if present
                $name = ltrim($textContent, '@');
                if (! empty($name)) {
                    $mentions[] = $name;
                }
            }
        }

        // Remove duplicates and return
        return array_unique($mentions);
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
