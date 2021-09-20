<?php
/**
 * RateLimits Manager
 * @author Mark
 */

namespace Minds\Core\Security\RateLimits;

use Minds\Core\Data\Sessions;
use Minds\Core\Di\Di;
use Minds\Core\Data\Redis;
use Minds\Entities\Entity;
use Minds\Entities\User;

class Manager
{
    /** @var Sessions */
    private $sessions;

    /** @var Delegates\Notification */
    private $notificationDelegate;

    /** @var Delegates\Analytics */
    private $analyticsDelegate;

    /** @var User */
    private $user;

    /** @var Entity */
    private $entity;

    /** @var string $key */
    private $key;

    /** @var KeyValueLimiter */
    private $kvLimiter;

    /** @var Redis\Client */
    protected $redis;

    const INTERACTION_RATE_LIMITS = [
        'subscribe' => [
            [
                'period' => 300, //5 minutes
                'threshold' => 50, //50 per 5 minutes
            ],
            [
                'period' => 3600, //1 hour
                'threshold' => 200, //200 per 1 hour
            ],
            [
                'period' => 86400, //1 day
                'threshold' => 400, //400 per 1 day
            ]
        ],
        'voteup' => [
            [
                'period' => 300, //5 minutes
                'threshold' => 150, //150 per 5 minutes, 10 per minute
            ],
            [
                'period' => 86400, //1 day
                'threshold' => 1000,
            ],
        ],
        'votedown' => [
            [
                'period' => 300, //5 minutes
                'threshold' => 150, //150 per 5 minutes, 10 per minute
            ],
            [
                'period' => 86400, //1 day
                'threshold' => 5,
            ],
        ],
        'comment' => [
            [
                'period' => 300, //5 minutes
                'threshold' => 75, //150 per 5 minutes, 10 per minute
            ],
            [
                'period' => 86400, //1 day
                'threshold' => 500,
            ]
        ],
        'remind' => [
            [
                'period' => 86400, //1 day
                'threshold' => 500,
            ]
        ]
    ];

    public function __construct(
        $sessions = null,
        $notificationDelegate = null,
        $analyticsDelegate = null,
        Redis\Client $redis = null,
        $kvLimiter = null,
    ) {
        $this->sessions = $sessions ?: new Sessions;
        $this->kvLimiter = $kvLimiter ?: new KeyValueLimiter();
        $this->redis = $redis ?? Di::_()->get('Redis');
        $this->notificationDelegate = $notificationDelegate ?: new Delegates\Notification;
        $this->analyticsDelegate = $analyticsDelegate ?: new Delegates\Analytics;
    }

    /**
     * Set user / actor
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set entity
     * @param Entity $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Set the interaction
     * @param string $key
     * @return $this
     */
    public function setInteraction($key)
    {
        return $this->setKey("interaction:$key");
    }

    /**
     * Set the key
     * @param string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = "ratelimited_$key";
        return $this;
    }

    /**
     * Set the limit to impose
     * @param int $length
     * @return $this
     */
    public function setLimitLength($length)
    {
        $this->limitLength = $length;
        return $this;
    }

    /**
     * Impose the rate limit
     * @return void
     */
    public function impose()
    {
        $this->user->set($this->key, time() + $this->limitLength);
        $this->user->save(); //TODO: update to new repo system soon

        //Send a notification
        $this->notificationDelegate->notify($this->user, $this->key, $this->limitLength);
        //Emit to analytics
        $this->analyticsDelegate->emit($this->user, $this->key, $this->limitLength);
    }

    /**
     * Return if a rate limit is imposed
     * @param User $user
     * @return bool
     */
    public function isLimited()
    {
        if (!$this->user->get($this->key)) {
            return false;
        }

        if ($this->user->get($this->key) < time()) {
            return false;
        }

        return true;
    }

    /**
     * Control rate limit for interactions.
     * This creates multiple redis records per period, per interaction
     * @param User $user
     * @param string $interaction
     * @return $this
     */
    public function control($user, $interaction)
    {
        $rateLimited = false;
        foreach (self::INTERACTION_RATE_LIMITS as $theInteraction => $rateLimits) {
            if ($theInteraction === $interaction) {
                foreach ($rateLimits as $rateLimit) {
                    try {
                        $key = "interaction:$interaction";
                        $this->kvLimiter
                            ->setKey($key) // e.g. interaction:comment:300
                            ->setValue($user->getGuid())
                            ->setSeconds($rateLimit['period'])
                            ->setMax($rateLimit['threshold'])
                            ->checkAndIncrement();
                    } catch (RateLimitExceededException $e) {
                        $this->notify($key, $user, $rateLimit['period']);
                        $rateLimited = true;
                        break;
                    }
                }
            }
        }

        return $rateLimited;
    }

    /**
     * handles sending notification and emitting to analytics
     * @param string $key
     * @param User $user
     * @param int $period
     * @return void
     */
    private function notify($key, $user, $period)
    {
        $guid = $user->getGuid();
        $interaction = explode(':', $key)[1];
        $recordKey = "ratelimit:$key-$guid:$period:notified";
        $notified = $this->redis->get($recordKey);

        if ($notified) return;

        //Send a notification
        $this->notificationDelegate->notify($user, $interaction, $period);
        //Emit to analytics
        $this->analyticsDelegate->emit($user, $key, $period);

        $this->redis
            ->multi()
            ->set($recordKey, true)
            ->expire($recordKey, $period)
            ->exec();
    }
}
