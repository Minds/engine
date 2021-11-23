<?php
namespace Minds\Common;

use Minds\Entities\User;

/**
 * We create a one-way Pseudonymous Identifier to protect users privacy. The Identifier consists of a hash between
 * the user_guid and the raw password, so only the user can generate this identifier on authentication.
 */
class PseudonymousIdentifier
{
    /** @var string */
    const COOKIE_NAME = "minds_psudeoid";

    protected User $user;

    public function __construct(protected ?Cookie $cookie = null)
    {
        $this->cookie = $cookie ?? new Cookie();
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
        $passwordPair = $password . $this->user->password; // For our key we need the real password and the already salted/hashed password to
        $identifier = hash_hmac('md5', (string) $this->user->getGuid(), $passwordPair);

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
}
