<?php
namespace Minds\Common;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * We create a one-way Pseudonymous Identifier to protect users privacy. The Identifier consists of a hash between
 * the user_guid and the raw password, so only the user can generate this identifier on authentication.
 */
class PseudonymousIdentifier
{
    /** @var string */
    const COOKIE_NAME = "minds_pseudoid";

    /** @var int */
    const COST = 11;

    /** @var int */
    const ID_LENGTH = 22;

    /** @var int */
    const SALT_LENGTH= 22;

    /** @var int */
    const DATA_LENGTH = 72;

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
        $key = $this->bcrypt(
            $this->hashAndTruncate($password.$this->user->password, static::DATA_LENGTH),
            $this->hashAndTruncate($this->getSessionsPrivateKey(), static::SALT_LENGTH),
            static::COST
        );

        // Our root identifier is the userGuid
        $identifier  = $this->bcrypt(
            (string) $this->user->getGuid(),
            $this->hashAndTruncate($key, static::SALT_LENGTH),
            static::COST
        );

        // Ensure we only return alphanumeric ids and make lower case
        $identifier = strtolower(preg_replace("/[^a-zA-Z0-9]+/", '', $this->hashAndTruncate($identifier, static::ID_LENGTH)));

        $this->cookie
            ->setName(static::COOKIE_NAME)
            ->setValue($identifier)
            ->setPath('/')
            ->setHttpOnly(false) // app needs to be able to access
            ->setSecure(true)
            ->setExpire(2147483647) // Do not expire
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

    /**
     * @param string $data - max 72 chars
     * @param string $salt - max 22 chars
     * @param int $cost
     * @return string
     */
    protected function bcrypt($data, $salt, $cost): string
    {
        $bcryptFnId = '$2y$';
        $cost = str_pad($cost, 2, '0', STR_PAD_LEFT);

        if (strlen($data) > 72) {
            throw new ServerErrorException("You can not provide more than 72 characters to bcrypt data param", 500);
        }

        if (strlen($salt) > 22) {
            throw new ServerErrorException("You can not provide more than 22 characters to bcrypt salt param", 500);
        }

        return crypt(
            $data,
            $bcryptFnId . $cost . '$' . base64_encode($salt) . '$'
        );
    }

    /**
     * Will return a hash and truncate the the string
     * @param string $text
     * @param int $limit
     * @return int
     */
    protected function hashAndTruncate($text, $limit = 22): string
    {
        return substr(base64_encode(hash('md5', $text, binary: true)), 0, $limit);
    }
}
