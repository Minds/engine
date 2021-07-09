<?php
namespace Minds\Core\Security\Password;

use Minds\Core\Security\Password\PwnedPasswords\Client;
use Exception;

/**
 * Password Manager
 * @package Minds\Core\Security\Password
 */
class Manager
{
    /** @var Client $client */
    protected $client;

    public function __construct(
        Client $client = null
    ) {
        $this->client = $client ?? new Client();
    }

    /**
     * Returns true if password pwn count exceeds threshold
     * @param $hash
     * @return int
     */
    public function getRisk(string $password): bool
    {
        if (!$password) {
            throw new Exception("Password required");
        }

        $hash = strtoupper(sha1($password));

        $hashPrefix = substr($hash, 0, 5);
        $hashSuffix = substr($hash, 5);

        $responseRows = $this->client->getRows($hashPrefix);

        // Allow users to save passwords if pwnedpasswords server error
        if (strlen($responseRows) === 0) {
            return false;
        }

        $responseArray = $this->rowsToArray($responseRows);

        $suffixes = array_column($responseArray, 'hashSuffix');
        $matchKey = array_search($hashSuffix, $suffixes, true);

        // Password has never been pwned - no risk
        if ($matchKey === false) {
            return false;
        }

        // Increase this number to allow more risk
        $riskThreshold = 10;

        $pwnCount = $responseArray[$matchKey]['count'];

        return $pwnCount >= $riskThreshold;
    }

    /**
     * Convert string of rows into associative array
     * @param string $rows
     * @return array
     */
    private function rowsToArray(string $rows): array
    {
        $rows = explode("\n", $rows);
        $array = [];
        foreach ($rows as $r) {
            $r = explode(":", $r);
            $array[] = [ 'hashSuffix' => $r[0], 'count' => $r[1] ];
        }

        return $array;
    }
}
