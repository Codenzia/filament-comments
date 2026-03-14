<?php

namespace Codenzia\FilamentComments\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LinkPreviewService
{
    /**
     * Extract all URLs from HTML comment body.
     *
     * @return array<string>
     */
    public function extractUrls(string $html): array
    {
        // Match URLs in href attributes and plain text URLs
        preg_match_all(
            '/https?:\/\/[^\s<>"\']+/i',
            strip_tags($html, '<a>'),
            $matches
        );

        // Also extract from href attributes
        preg_match_all(
            '/href=["\']?(https?:\/\/[^\s"\'<>]+)/i',
            $html,
            $hrefMatches
        );

        $urls = array_unique(array_merge($matches[0] ?? [], $hrefMatches[1] ?? []));

        // Filter out image URLs and internal URLs
        return array_values(array_filter($urls, function (string $url) {
            return ! preg_match('/\.(jpg|jpeg|png|gif|svg|webp|ico)(\?.*)?$/i', $url);
        }));
    }

    /**
     * Fetch Open Graph metadata for a URL.
     *
     * @return array{title: ?string, description: ?string, image: ?string, domain: string}|null
     */
    public function fetchPreview(string $url): ?array
    {
        $cacheTtl = config('filament-comments.link_previews.cache_ttl', 3600);
        $cacheKey = 'fc_link_preview_' . md5($url);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($url) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'FilamentCommentsBot/1.0'])
                    ->get($url);

                if (! $response->successful()) {
                    return null;
                }

                $html = $response->body();

                return [
                    'url' => $url,
                    'title' => $this->extractMeta($html, 'og:title')
                        ?? $this->extractMeta($html, 'twitter:title')
                        ?? $this->extractTitle($html),
                    'description' => $this->extractMeta($html, 'og:description')
                        ?? $this->extractMeta($html, 'twitter:description')
                        ?? $this->extractMeta($html, 'description'),
                    'image' => $this->extractMeta($html, 'og:image')
                        ?? $this->extractMeta($html, 'twitter:image'),
                    'domain' => parse_url($url, PHP_URL_HOST) ?? $url,
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * Fetch previews for all URLs in a comment body.
     *
     * @return array<array{url: string, title: ?string, description: ?string, image: ?string, domain: string}>
     */
    public function fetchPreviews(string $html): array
    {
        if (! config('filament-comments.link_previews.enabled', true)) {
            return [];
        }

        $urls = $this->extractUrls($html);
        $previews = [];

        // Limit to 3 previews per comment
        foreach (array_slice($urls, 0, 3) as $url) {
            $preview = $this->fetchPreview($url);
            if ($preview && ($preview['title'] || $preview['description'])) {
                $previews[] = $preview;
            }
        }

        return $previews;
    }

    protected function extractMeta(string $html, string $property): ?string
    {
        // Try property attribute (og: tags)
        if (preg_match('/<meta[^>]+property=["\']' . preg_quote($property) . '["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }

        // Try name attribute (standard meta tags)
        if (preg_match('/<meta[^>]+name=["\']' . preg_quote($property) . '["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }

        // Try content-first order
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)[^>]+(?:property|name)=["\']' . preg_quote($property) . '["\'][^>]*/i', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        }

        return null;
    }

    protected function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m)) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }

        return null;
    }
}
