<?php

namespace Codenzia\FilamentComments\Helpers;

final class Helper
{
    public static function getResolvedUrl(string $key): string
    {
        if (blank(config('codenzia-comments.mentionable.url'))) {
            return '#';
        }

        return url(str_replace('{id}', $key, config('codenzia-comments.mentionable.url')));

    }
}
