<?php
/**
 * Channels manager
 */

namespace Minds\Core\Channels;

use Minds\Core\Channels\Delegates\MetricsDelegate;
use Minds\Core\Di\Di;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Entities\User;
use Minds\Core\Channels\Delegates\Artifacts;
use Minds\Core\Config\Config;
use Psr\SimpleCache\CacheInterface;

class Manager
{
    /** @var string[] */
    const DELETION_DELEGATES = [
        Artifacts\EntityDelegate::class,
        Artifacts\LookupDelegate::class,
        Artifacts\UserIndexesDelegate::class,
        Artifacts\UserEntitiesDelegate::class,
        Artifacts\SubscribersDelegate::class,
        Artifacts\SubscriptionsDelegate::class,
        Artifacts\ElasticsearchDocumentsDelegate::class,
        Artifacts\CommentsDelegate::class,
    ];

    /** @var string[] */
    const TENANT_DELETION_DELEGATES = [
        Artifacts\MySQL\EntityDelegate::class,
        Artifacts\MySQL\FriendsDelegate::class,
        Artifacts\CommentsDelegate::class,
        Artifacts\ElasticsearchDocumentsDelegate::class,
    ];

    /** @var User $user */
    protected $user;

    /** @var Delegates\Artifacts\Factory */
    protected $artifactsDelegatesFactory;

    /** @var MetricsDelegate */
    protected $metricsDelegate;

    /** @var Delegates\Logout */
    protected $logoutDelegate;

    /** @var QueueClient */
    protected $queueClient;

    /** @var CacheInterface */
    protected $cache;

    /** @var Config */
    protected $config;

    /**
     * Manager constructor.
     * @param Delegates\Artifacts\Factory $artifactsDelegatesFactory
     * @param Delegates\Logout $logoutDelegate
     * @param QueueClient $queueClient
     * @param CacheInterface $cache
     * @param Config $config
     */
    public function __construct(
        $artifactsDelegatesFactory = null,
        $metricsDelegate = null,
        $logoutDelegate = null,
        $queueClient = null,
        $cache = null,
        $config = null
    ) {
        $this->artifactsDelegatesFactory = $artifactsDelegatesFactory ?: new Delegates\Artifacts\Factory();
        $this->metricsDelegate = $metricsDelegate ?: new MetricsDelegate();
        $this->logoutDelegate = $logoutDelegate ?: new Delegates\Logout();
        $this->queueClient = $queueClient ?: Di::_()->get('Queue');
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
        $this->config = $config ?? Di::_()->get(Config::class);
    }

    /**
     * Set the user to manage
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function snapshot()
    {
        if (!$this->user) {
            throw new \Exception('Missing User');
        }

        $userGuid = $this->user->guid;

        foreach (static::DELETION_DELEGATES as $delegateClassName) {
            try {
                $delegate = $this->artifactsDelegatesFactory->build($delegateClassName);
                $done = $delegate->snapshot($userGuid);

                if (!$done) {
                    throw new \Exception("{$delegateClassName} snapshot failed for {$userGuid}");
                }
            } catch (\Exception $e) {
                // TODO: Fail?
                error_log((string) $e);
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes a channel
     * @return bool
     * @throws \Exception
     */
    public function delete()
    {
        if (!$this->user) {
            throw new \Exception('Missing User');
        }

        $this->logoutDelegate->logout($this->user);

        $this->queueClient
            ->setQueue('ChannelDeferredOps')
            ->send([
                'type' => 'delete',
                'user_guid' => $this->user->guid
            ]);

        return true;
    }

    public function deleteCleanup()
    {
        if (!$this->user) {
            throw new \Exception('Missing User');
        }

        $userGuid = $this->user->guid;

        $delegateClassNames = (bool) $this->config->get('tenant_id') ?
            static::TENANT_DELETION_DELEGATES :
            static::DELETION_DELEGATES;

        foreach ($delegateClassNames as $delegateClassName) {
            try {
                $delegate = $this->artifactsDelegatesFactory->build($delegateClassName);
                $done = $delegate->delete($userGuid);

                if (!$done) {
                    throw new \Exception("{$delegateClassName} deletion failed for {$userGuid}");
                }
            } catch (\Exception $e) {
                // TODO: Fail?
                error_log((string) $e);
                return false;
            }
        }

        $this->metricsDelegate->onDelete($this->user);
        $this->logoutDelegate->logout($this->user);

        return true;
    }

    /**
     * @param $userGuid
     * @return bool
     * @throws \Exception
     */
    public function restore($userGuid)
    {
        if (!$userGuid) {
            throw new \Exception('Missing User GUID');
        }

        $success = true;

        foreach (static::DELETION_DELEGATES as $delegateClassName) {
            try {
                $delegate = $this->artifactsDelegatesFactory->build($delegateClassName);
                $done = $delegate->restore($userGuid);

                if (!$done) {
                    throw new \Exception("{$delegateClassName} restore failed for {$userGuid}");
                }
            } catch (\Exception $e) {
                // TODO: Fail?
                error_log((string) $e);
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Flush the cache for a user entity
     * @param User $user
     * @return void
     */
    public function flushCache(User $user): void
    {
        $this->cache->delete("entity:{$user->getGuid()}");
    }
}
