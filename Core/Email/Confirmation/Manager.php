<?php
/**
 * Manager
 *
 * @author edgebal
 */

namespace Minds\Core\Email\Confirmation;

use Exception;
use Minds\Common\Jwt;
use Minds\Common\Urn;
use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Queue\Client as QueueClientFactory;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Entities\User;
use Minds\Entities\UserFactory;

class Manager
{    // timespan to check rate limit.
    const RATE_LIMIT_TIMESPAN = 60;

    // max amount of occurrence in timespan.
    const RATE_LIMIT_MAX = 1;

    /** @var Config */
    protected $config;

    /** @var Jwt */
    protected $jwt;

    /** @var QueueClient */
    protected $queue;

    /** @var Client */
    protected $es;

    /** @var UserFactory */
    protected $userFactory;

    /** @var Resolver */
    protected $resolver;

    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var User */
    protected $user;

    /** @var KeyValueLimiter */
    protected $kvLimiter;

    /**
     * Manager constructor.
     * @param Config $config
     * @param Jwt $jwt
     * @param QueueClient $queue
     * @param Client $elasticsearch
     * @param UserFactory $userFactory
     * @param Resolver $resolver
     * @param EventsDispatcher $eventsDispatcher
     * @throws Exception
     */
    public function __construct(
        $config = null,
        $jwt = null,
        $queue = null,
        $elasticsearch = null,
        $userFactory = null,
        $resolver = null,
        $eventsDispatcher = null,
        $kvLimiter = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->jwt = $jwt ?: new Jwt();
        $this->queue = $queue ?: QueueClientFactory::build();
        $this->es = $elasticsearch ?: Di::_()->get('Database\ElasticSearch');
        $this->userFactory = $userFactory ?: new UserFactory();
        $this->resolver = $resolver ?: new Resolver();
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->kvLimiter = $kvLimiter ?? Di::_()->get("Security\RateLimits\KeyValueLimiter");
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

        // Can throw RateLimitException.
        $this->kvLimiter
            ->setKey('email-confirmation')
            ->setValue($this->user->getGuid())
            ->setSeconds(self::RATE_LIMIT_TIMESPAN)
            ->setMax(self::RATE_LIMIT_MAX)
            ->checkAndIncrement();

        $this->generateConfirmationToken();

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

        if ($confirmation['exp'] < new \DateTime()) {
            throw new Exception('Confirmation token expired');
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

        $this->approveConfirmation($user);

        return true;
    }

    /**
     * Approve confirmation for a user. To be called only after validating a user
     * is trustworthy, e.g. by validating their email.
     * @param User $user - user to approve confirmation for.
     * @return void
     */
    public function approveConfirmation(User $user): void
    {
        $user
            ->setEmailConfirmationToken('')
            ->setEmailConfirmedAt(time())
            ->save();

        // re-index the user so email_confirmed_at gets updated in ElasticSearch
        $this->eventsDispatcher->trigger('search:index', 'all', [
            'entity' => $user,
            'immediate' => true,
        ]);

        $this->queue
            ->setQueue('WelcomeEmail')
            ->send([
                'user_guid' => (string) $user->guid,
            ]);
    }

    /**
     * Fetches one day old unverified users
     */
    public function fetchNewUnverifiedUsers()
    {
        $must = [
            [
                'range' => [
                    'time_created' => [
                        'lt' => strtotime('midnight today'),
                        'gte' => strtotime('midnight yesterday'),
                    ],
                ],

            ],
        ];

        $must_not = [
            [
                'exists' => [
                    'field' => 'email_confirmed_at',
                ],
            ],
        ];

        $query = [
            'index' => $this->config->get('elasticsearch')['indexes']['search_prefix'] . '-user',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'must_not' => $must_not,
                    ],
                ],
            ],
        ];

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->es->request($prepared);

        $urns = [];
        $users = [];

        if (isset($result) && isset($result['hits']) && isset($result['hits']['hits'])) {
            foreach ($result['hits']['hits'] as $r) {
                try {
                    $urns[] = new Urn('urn:user:' . $r['_source']['guid']);
                } catch (\Exception $e) {
                    error_log("[Email\Confirmation\Manager::fetchNewUnverifiedUsers] {$e->getMessage()}");
                }
            }

            $this->resolver->setUrns($urns);
            $users = $this->resolver->fetch();
        }

        return $users;
    }

    /**
     * Generates an email confirmation token and saves it to instance member user
     * or returns an existing cached token.
     * @return self
     */
    private function generateConfirmationToken(): self
    {
        $existingToken = $this->user->getEmailConfirmationToken();

        // if existing token is valid, we do not need to generate a new one.
        if ($existingToken && $this->isTokenValid($existingToken)) {
            return $this;
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

        return $this;
    }

    /**
     * Determine whether email confirmation token is valid.
     * @param string $jwt - jwt string to check validity of.
     * @return boolean true if token is valid.
     */
    private function isTokenValid(string $jwt): bool
    {
        try {
            $config = $this->config->get('email_confirmation');

            // @throws Exception if invalid jwt.
            $confirmation = $this->jwt
                ->setKey($config['signing_key'])
                ->decode($jwt);

            if (
                !$confirmation ||
                !$confirmation['user_guid'] ||
                !$confirmation['code']
            ) {
                throw new Exception('Invalid JWT');
            }

            return $confirmation['exp'] > new \DateTime();
        } catch (\Exception $e) {
            return false;
        }
    }
}
