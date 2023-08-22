<?php
namespace Minds\Core\ActivityPub\Helpers;

class JsonLdHelper
{
    const CONTEXT = 'https://www.w3.org/ns/activitystreams';

    /**
     * Convienient to check if string in array or string is a match
     */
    public static function equalsOrIncludes(string|array $haystack, string $needle): bool
    {
        return is_array($haystack) ? in_array($needle, $haystack, true) : $haystack === $needle;
    }

    /**
     * Verifies if the provided context matches the context we support
     */
    public static function isSupportedContext($json): bool
    {
        if (!isset($json['@context'])) {
            return false;
        }
        return static::equalsOrIncludes($json['@context'], static::CONTEXT);
    }

    public static function getValueOrId($value): ?string
    {
        return is_string($value) || is_null($value) ? $value : $value['id'];
    }

    /**
     * Returns a domain from a uri
     */
    public static function getDomainFromUri($uri): string
    {
        $parsed = parse_url($uri);
        return $parsed['host'];
    }
}
