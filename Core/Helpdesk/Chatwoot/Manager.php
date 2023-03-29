<?php
declare(strict_types=1);

namespace Minds\Core\Helpdesk\Chatwoot;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * Chatwoot Manager
 */
class Manager
{
    public function __construct(
        private ?Config $config = null,
    ) {
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Generates HMAC for the given user to confirm their identity with Chatwoot.
     * @param User $user - user to get HMAC for.
     * @return string generated HMAC.
     */
    public function getUserHmac(User $user): string
    {
        $key = $this->config->get('chatwoot')['signing_key'] ?? false;
        if (!$key) {
            throw new ServerErrorException('No signing key set for chatwoot');
        }

        $message = (string) $user->getGuid();
        return hash_hmac('sha256', $message, $key);
    }//
}
