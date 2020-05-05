<?php

namespace Minds\Core\Suggestions;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
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

    /** @var string $type */
    private $type = 'user';

    public function __construct(
        $repository = null,
        $entitiesBuilder = null,
        $suggestedFeedsManager = null,
        $subscriptionsManager = null,
        $checkRateLimit = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->entitiesBuilder = $entitiesBuilder ?: new EntitiesBuilder();
        //$this->suggestedFeedsManager = $suggestedFeedsManager ?: Di::_()->get('Feeds\Suggested\Manager');
        $this->subscriptionsManager = $subscriptionsManager ?: Di::_()->get('Subscriptions\Manager');
        $this->checkRateLimit = $checkRateLimit ?: new CheckRateLimit();
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

        $response = $this->repository->getList($opts);

        if (!count($response)) {
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
        $this->subscriptionsManager->setSubscriber($this->user);
        if ($this->subscriptionsManager->getSubscriptionsCount() > 1) {
            return new Response();
        }

        $opts = array_merge([
            'user_guid' => $this->user->getGuid(),
            'type' => 'user',
        ], $opts);

        $response = new Response();

        $guids = [
            626772382194872329,
            100000000000065670,
            100000000000081444,
            732703596054847489,
            884147802853089287,
            100000000000000341,
            823662468030013460,
            942538426693984265,
            607668752611287060,
            602551056588615697,
        ];

        foreach ($guids as $i => $guid) {
            if ($i >= $opts['limit']) {
                continue;
            }
            $suggestion = new Suggestion();
            $suggestion->setEntityGuid($guid);
            $response[] = $suggestion;
        }

        return $response;
    }
}
