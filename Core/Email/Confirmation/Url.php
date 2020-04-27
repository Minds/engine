<?php
/**
 * Url
 *
 * @author edgebal
 */

namespace Minds\Core\Email\Confirmation;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;

class Url
{
    /** @var string */
    const EMAIL_CONFIRMATION_PATH = '/email-confirmation';

    /** @var Config */
    protected $config;

    /** @var User */
    protected $user;

    /**
     * ConfirmationUrlDelegate constructor.
     * @param Config $config
     */
    public function __construct(
        $config = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param User $user
     * @return Url
     */
    public function setUser(User $user): Url
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param array $params
     * @return string
     */
    public function generate(array $params = []): string
    {
        return sprintf(
            '%s%s?%s',
            rtrim($this->config->get('site_url'), '/'),
            static::EMAIL_CONFIRMATION_PATH,
            http_build_query(array_merge($params, [
                '__e_cnf_token' => $this->user->getEmailConfirmationToken(),
            ]))
        );
    }
}
