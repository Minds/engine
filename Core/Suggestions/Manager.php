<?php

namespace Minds\Core\Suggestions;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Features;
use Minds\Core\Suggestions\Delegates\CheckRateLimit;
use Minds\Entities\User;

class Manager
{
    /** @var $repository */
    private $repository;

    /** @var EntitiesBuilder $entitiesBuilder */
    private $entitiesBuilder;

    /** @var \Minds\Core\Subscriptions\Manager */
    private $subscriptionsManager;

    /** @var User $user */
    private $user;

    /** @var CheckRateLimit */
    private $checkRateLimit;

    /** @var Features\Manager */
    private $features;

    /** @var string $type */
    private $type = 'user';

    public function __construct(
        $repository = null,
        $entitiesBuilder = null,
        $suggestedFeedsManager = null,
        $subscriptionsManager = null,
        $checkRateLimit = null,
        $features = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->entitiesBuilder = $entitiesBuilder ?: new EntitiesBuilder();
        //$this->suggestedFeedsManager = $suggestedFeedsManager ?: Di::_()->get('Feeds\Suggested\Manager');
        $this->subscriptionsManager = $subscriptionsManager ?: Di::_()->get('Subscriptions\Manager');
        $this->checkRateLimit = $checkRateLimit ?: new CheckRateLimit();
        $this->features = $features ?? new Features\Manager();
    }

    /**
     * Set the user to return data for.
     *
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the type to return data for.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Return a list of users.
     *
     * @param array $opts
     *
     * @return Response
     */
    public function getList($opts = []): Response
    {
        if (!$this->features->has('suggestions')) {
            return new Response([]);
        }

        $opts = array_merge([
            'limit' => 12,
            'paging-token' => '',
            'type' => $this->type,
        ], $opts);

        if (!$this->checkRateLimit->check($this->user->guid)) {
            return new Response([]);
        }

        $opts['user_guid'] = $this->user->getGuid();

        $opts['limit'] = $opts['limit'] * 3; // To prevent removed channels or closed groups


        if ($this->subscriptionsManager->setSubscriber($this->user)
            ->getSubscriptionsCount() > 1) {
            $response = $this->repository->getList($opts);
        } else {
            $response = $this->getFallbackSuggested($opts);
        }

        // Hydrate the entities
        // TODO: make this a bulk request vs sequential
        $response = $response->map(function ($suggestion) {
            $entity = $suggestion->getEntity() ?: $this->entitiesBuilder->single($suggestion->getEntityGuid());
            if (!$entity) {
                error_log("{$suggestion->getEntityGuid()} suggested user not found");
                return null;
            }
            if ($entity->getDeleted()) {
                error_log("Deleted entity ".$entity->guid." has been omitted from suggestions t-".time());
                return null;
            }
            if ($entity->getType() === 'group' && !$entity->isPublic()) {
                return null;
            }
            if (
                $entity->getType() === 'user' &&
                ($entity->banned === 'yes' || $entity->enabled != 'yes')
            ) {
                return null;
            }
            if ($entity->getType() === 'user') {
                $entity->exportCounts = true;
            }
            $suggestion->setEntity($entity);
            return $suggestion;
        });

        // Remove missing entities
        $response = $response->filter(function ($suggestion) {
            return $suggestion && $suggestion->getEntity();
        });

        $response = $response->filter(function ($suggestion, $i) use ($opts) {
            return $i < ($opts['limit'] / 3);
        });


        return $response;
    }

    private function getFallbackSuggested($opts = [])
    {
        $opts = array_merge([
            'user_guid' => $this->user->getGuid(),
            'type' => 'user',
        ], $opts);

        $response = new Response();

        $users = $this->subscriptionsManager->getList([
            'guid' => '100000000000000519',
            'type' => 'subscriptions',
            'hydrate' => false,
            'limit' => 500,
        ]);

        $opts['user_guids'] = array_map(function ($user) {
            return $user;
        }, $users->toArray());

        return $this->repository->getList($opts);
    }
}
