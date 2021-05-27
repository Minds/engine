<?php
namespace Minds\Helpers;

use Exception;

class Urn
{
    /**
     * Builds a new URN
     * @param string $nid
     * @param string[] $nss
     * @return string
     */
    public static function build(string $nid, array $nss): string
    {
        return "urn:{$nid}:" . implode('/', array_map('self::encodeNssFragment', $nss));
    }

    /**
     * Decodes a URN. Zeroth index will be the NID, rest will be NSS fragments.
     * @param string $urn
     * @param string $nid
     * @param bool $strict
     * @return string[]
     * @throws Exception
     */
    public static function parse(string $urn, string $nid, $strict = true): array
    {
        $fragments = explode(':', $urn);

        if (count($fragments) !== 3) {
            if ($strict) {
                throw new Exception('Invalid URN');
            } else {
                return [];
            }
        } elseif (($fragments[0] ?? null) !== 'urn') {
            if ($strict) {
                throw new Exception('Invalid URN');
            } else {
                return [];
            }
        } elseif ($fragments[1] !== $nid) {
            if ($strict) {
                throw new Exception('Invalid URN NID');
            } else {
                return [];
            }
        }

        return array_map('self::decodeNssFragment', explode('/', $fragments[2]));
    }

    /**
     * @param string $urn
     * @param bool $strict
     * @return string
     * @throws Exception
     */
    public function getNid(string $urn, $strict = true): string
    {
        $fragments = explode(':', $urn);

        if (count($fragments) !== 3) {
            if ($strict) {
                throw new Exception('Invalid URN');
            } else {
                return [];
            }
        } elseif ($fragments[0] ?? null !== 'urn') {
            if ($strict) {
                throw new Exception('Invalid URN');
            } else {
                return [];
            }
        }

        return $fragments[1];
    }

    /**
     * Encodes a NSS fragment
     * @param string $fragment
     * @return string
     */
    public static function encodeNssFragment(string $fragment): string
    {
        return rawurlencode((string) $fragment);
    }

    /**
     * Decodes a NSS fragment
     * @param string $encodedFragment
     * @return string
     */
    public static function decodeNssFragment(string $encodedFragment): string
    {
        return rawurldecode((string) $encodedFragment);
    }
}
