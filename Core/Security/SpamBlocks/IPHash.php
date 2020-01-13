<?php
/**
 * SpamBlocks Manager
 */
namespace Minds\Core\Security\SpamBlocks;

class IPHash
{
    /** @var Manager $manager */
    private $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?: new Manager;
    }

    /**
     * Generates a temporary ID based on a truncated IP address hash
     * @param string $ip
     * @param int $bits
     * @return int
     */
    public function generateTempId(string $ip, int $bits = 60): int
    {
        $hash = hash('sha256', $ip);
        return -1 * hexdec(substr($hash, 0, floor($bits / 4)));
    }

    /**
     * Return if an IP is valid
     */
    public function isValid($ip)
    {
        $hash = hash('sha256', $ip);
        $spamBlock = new SpamBlock();
        $spamBlock->setKey('ip_hash')
            ->setValue($hash);
        return !$this->manager->isSpam($spamBlock);
    }
}
