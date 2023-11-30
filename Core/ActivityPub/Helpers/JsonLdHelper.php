<?php
namespace Minds\Core\ActivityPub\Helpers;

use Minds\Core\ActivityPub\Types\Core\ObjectType;

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

    public static function getValueOrId(string|null|ObjectType|array $value): ?string
    {
        if (is_string($value) || is_null($value)) {
            return $value;
        }
        if ($value instanceof ObjectType) {
            return $value->id;
        }
        return $value['id'];
    }

    /**
     * Returns a domain from a uri
     */
    public static function getDomainFromUri($uri): string
    {
        $parsed = parse_url($uri);
        $host = $parsed['host'];

        // Very nasty hack to make ...@minds.com usernames exclude the www.
        // TODO:
        if ($host === 'www.minds.com') {
            $host = 'minds.com';
        }

        return $host;
    }
}
