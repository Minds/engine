<?php
/**
 * Resolver.
 *
 * @author emi
 */

namespace Minds\Core\Entities;

use Minds\Common\Urn;
use Minds\Core\Entities\Delegates\BoostGuidResolverDelegate;
use Minds\Core\Entities\Delegates\CommentGuidResolverDelegate;
use Minds\Core\Entities\Delegates\EntityGuidResolverDelegate;
use Minds\Core\Entities\Delegates\EventsResolverDelegate;
use Minds\Core\Entities\Delegates\FilterEntitiesDelegate;
use Minds\Core\Entities\Delegates\ResolverDelegate;
use Minds\Core\Entities\Delegates\SystemPushNotificationResolverDelegate;
use Minds\Core\Security\ACL;
use Minds\Entities\User;

/**
 *
 */
class Resolver
{
    /** @var ResolverDelegate[] $entitiesBuilder */
    protected $resolverDelegates;

    /** @var ACL */
    protected $acl;

    /** @var User */
    protected $user;

    /** @var Urn[] */
    protected $urns = [];

    /** @var array */
    protected $opts = [];

    /**
     * Resolver constructor.
     * @param ResolverDelegate[] $resolverDelegates
     * @param ACL $acl
     */
    public function __construct($resolverDelegates = null, $acl = null)
    {
        $this->resolverDelegates = $resolverDelegates ?: [
            EventsResolverDelegate::class => new EventsResolverDelegate(),
            EntityGuidResolverDelegate::class => new EntityGuidResolverDelegate(),
            BoostGuidResolverDelegate::class => new BoostGuidResolverDelegate(),
            CommentGuidResolverDelegate::class => new CommentGuidResolverDelegate(),
            SystemPushNotificationResolverDelegate::class => new SystemPushNotificationResolverDelegate(),
        ];

        $this->acl = $acl ?: ACL::_();
    }

    /**
     * @param User|null $user
     * @return Resolver
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param Urn[] $urns
     * @return Resolver
     */
    public function setUrns(array $urns)
    {
        $this->urns = $urns;
        return $this;
    }

    /**
     * @param array $opts
     * @return Resolver
     */
    public function setOpts(array $opts)
    {
        $this->opts = $opts;
        return $this;
    }

    /**
     * @return array
     */
    public function fetch(): array
    {
        // Setup batches for bulk operations on some resolvers
        $batches = [];

        foreach ($this->resolverDelegates as $resolverDelegateClassName => $resolverDelegate) {
            $batches[$resolverDelegateClassName] = [];
        }

        foreach ($this->urns as $urn) {
            foreach ($this->resolverDelegates as $resolverDelegateClassName => $resolverDelegate) {
                if ($resolverDelegate->shouldResolve($urn)) {
                    $batches[$resolverDelegateClassName][] = $urn;
                    break;
                }
            }
        }

        // Setup URN map of resolved entities

        $resolvedMap = [];

        foreach ($batches as $resolverDelegateClassName => $batch) {
            $resolverDelegate = $this->resolverDelegates[$resolverDelegateClassName];
            $resolvedEntities = $resolverDelegate->resolve($batch, $this->opts);

            if (!is_array($resolvedEntities)) {
                continue;
            }

            foreach ($resolvedEntities as $resolvedEntity) {
                $urn = $resolverDelegate->asUrn($resolvedEntity);
                $resolvedMap[$urn] = $resolverDelegate->map($urn, $resolvedEntity);
            }
        }

        // Sort as provided by parameters

        $sorted = [];

        foreach ($resolvedMap as $entity) {
            $sorted[] = $entity ?? null;
        }

        // Filter out invalid entities

        $sorted = array_filter($sorted, function ($entity) {
            return (bool) $entity;
        });

        // Filter out forbidden entities
        $filterDelegate = new FilterEntitiesDelegate($this->user, time(), $this->acl);
        $sorted = $filterDelegate->filter($sorted);

        //

        return $sorted;
    }

    /**
     * Gets an entity URN and returns the Entity object
     * @param $urn
     * @return mixed
     */
    public function single($urn): mixed
    {
        $this->urns = [$urn];
        $entities = $this->fetch();
        return $entities[0];
    }
}
