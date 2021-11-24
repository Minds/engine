<?php
namespace Minds\Common;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;

/**
 * We create a one-way Pseudonymous Identifier to protect users privacy. The Identifier consists of a hash between
 * the user_guid and the raw password, so only the user can generate this identifier on authentication.
 */
class PseudonymousIdentifier
{
    /** @var string */
    const COOKIE_NAME = "minds_psudeoid";

    /** @var int */
    const COST = 1024;

    /** @var int */
    const ID_LENGTH = 20;

    protected User $user;

    public function __construct(protected ?Cookie $cookie = null, protected ?Config $config = null)
    {
        $this->cookie = $cookie ?? new Cookie();
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * @param User $user
     * @return PseudonymousIdentifier
     */
    public function setUser(User $user): PseudonymousIdentifier
    {
        $class = clone $this;
        $class->user = $user;
        return $class;
    }

    /**
     * Generates and returns the identifier
     * @param string $password
     * @return string
     */
    public function generateWithPassword(string $password): string
    {
        // For our key we need the real password and the already salted/hashed password
        // we then hash that with our global session private key
        $key = hash_pbkdf2('sha256', crypt($password, '$2y$10$' . base64_encode($this->user->password) . '$'), $this->getSessionsPrivateKey(), static::COST);

        // Our root identifier is the userGuid
        $identifier = hash_pbkdf2('sha256', (string) $this->user->getGuid(), $key, static::COST, static::ID_LENGTH);

        $this->cookie
            ->setName(static::COOKIE_NAME)
            ->setValue($identifier)
            ->setPath('/')
            ->setHttpOnly(false) // app needs to be able to access
            ->setSecure(true)
            ->create();

        // Make it aware in the current process
        $_COOKIE[static::COOKIE_NAME] = $identifier;

        return $identifier;
    }

    /**
     * To be called for public server side actions only
     * @return string|null
     */
    public function getId(): ?string
    {
        return $_COOKIE[static::COOKIE_NAME] ?? null;
    }

    /**
     * Will return the global sessions private key used for hashing
     * @return string
     */
    protected function getSessionsPrivateKey(): string
    {
        return file_get_contents($this->config->get('sessions')['private_key']);
    }
}
