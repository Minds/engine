<?php
/**
 * Manager
 *
 * @author edgebal
 */

namespace Minds\Core\Email\Confirmation;

use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Queue\Client as QueueClientFactory;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Entities\User;
use Minds\Entities\UserFactory;

class Manager
{
    /** @var Config */
    protected $config;

    /** @var Jwt */
    protected $jwt;

    /** @var QueueClient */
    protected $queue;

    /** @var UserFactory */
    protected $userFactory;

    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var User */
    protected $user;

    /**
     * Manager constructor.
     * @param Config $config
     * @param Jwt $jwt
     * @param QueueClient $queue
     * @param UserFactory $userFactory
     * @param EventsDispatcher $eventsDispatcher
     * @throws Exception
     */
    public function __construct(
        $config = null,
        $jwt = null,
        $queue = null,
        $userFactory = null,
        $eventsDispatcher = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->jwt = $jwt ?: new Jwt();
        $this->queue = $queue ?: QueueClientFactory::build();
        $this->userFactory = $userFactory ?: new UserFactory();
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    /**
     * @param User $user
     * @return Manager
     */
    public function setUser(User $user): Manager
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function sendEmail(): void
    {
        if (!$this->user) {
            throw new Exception('User not set');
        }

        if ($this->user->isEmailConfirmed()) {
            throw new Exception('User email was already confirmed');
        }

        $config = $this->config->get('email_confirmation');

        $now = time();
        $expires = $now + $config['expiration'];

        $token = $this->jwt
            ->setKey($config['signing_key'])
            ->encode([
                'user_guid' => (string) $this->user->guid,
                'code' => $this->jwt->randomString(),
            ], $expires, $now);

        $this->user
            ->setEmailConfirmationToken($token)
            ->save();

        $this->eventsDispatcher->trigger('confirmation_email', 'all', [
            'user_guid' => (string) $this->user->guid,
            'cache' => false,
        ]);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function reset(): bool
    {
        if (!$this->user) {
            throw new Exception('User not set');
        }

        $this->user
            ->setEmailConfirmationToken('')
            ->setEmailConfirmedAt(0);

        return (bool) $this->user
            ->save();
    }

    /**
     * @param string $jwt
     * @return bool
     * @throws Exception
     */
    public function confirm(string $jwt): bool
    {
        $config = $this->config->get('email_confirmation');

        if ($this->user) {
            throw new Exception('Confirmation user is inferred from JWT');
        }

        $confirmation = $this->jwt
            ->setKey($config['signing_key'])
            ->decode($jwt); // Should throw if expired

        if (
            !$confirmation ||
            !$confirmation['user_guid'] ||
            !$confirmation['code']
        ) {
            throw new Exception('Invalid JWT');
        }

        $user = $this->userFactory->build($confirmation['user_guid'], false);

        if (!$user || !$user->guid) {
            throw new Exception('Invalid user');
        } elseif ($user->isEmailConfirmed()) {
            throw new Exception('User email was already confirmed');
        }

        $data = $this->jwt
            ->setKey($config['signing_key'])
            ->decode($user->getEmailConfirmationToken());

        if (
            $data['user_guid'] !== $confirmation['user_guid'] ||
            $data['code'] !== $confirmation['code']
        ) {
            throw new Exception('Invalid confirmation token data');
        }

        $user
            ->setEmailConfirmationToken('')
            ->setEmailConfirmedAt(time())
            ->save();

        $this->queue
            ->setQueue('WelcomeEmail')
            ->send([
                'user_guid' => (string) $user->guid,
            ]);

        return true;
    }
}
