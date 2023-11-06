<?php

namespace Minds\Core\Suggestions;

use Minds\Common\Repository\Response;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Recommendations\Algorithms\SuggestedGroups\SuggestedGroupsRecommendationsAlgorithm;
use Minds\Entities\User;
use Minds\Core\Security\Block;
use Minds\Core\Security\RateLimits\InteractionsLimiter;
use Minds\Core\Subscriptions;
use Minds\Core\Suggestions\DefaultTagMapping\Manager as DefaultTagMappingManager;

class Manager
{
    /** @var $repository */
    private $repository;

    /** @var EntitiesBuilder $entitiesBuilder */
    private $entitiesBuilder;

    /** @var Subscriptions\Manager */
    private $subscriptionsManager;

    /** @var User $user */
    private $user;

    /** @var InteractionsLimiter */
    private $interactionsLimiter;

    /** @var Block\Manager */
    private $blockManager;

    /** @var string $type */
    private $type = 'user';

    /** @var Config */
    protected $config;

    public function __construct(// @phpstan-ignore-line
        $repository = null,
        $entitiesBuilder = null,
        $suggestedFeedsManager = null,
        $subscriptionsManager = null,
        $interactionsLimiter = null,
        Config $config = null,
        private ?DefaultTagMappingManager $defaultTagMappingManager = null,
        private ?SuggestedGroupsRecommendationsAlgorithm $suggestedGroupsRecommendationsAlgorithm = null,
        private ?Logger $logger = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->entitiesBuilder = $entitiesBuilder ?: new EntitiesBuilder();
        //$this->suggestedFeedsManager = $suggestedFeedsManager ?: Di::_()->get('Feeds\Suggested\Manager');
        $this->subscriptionsManager = $subscriptionsManager ?: Di::_()->get(Subscriptions\Manager::class);
        $this->interactionsLimiter = $interactionsLimiter ?: new InteractionsLimiter();
        $this->blockManager = $blockManager ?? Di::_()->get('Security\Block\Manager');
        $this->config = $config ?? Di::_()->get('Config');
        $this->defaultTagMappingManager ??= Di::_()->get(DefaultTagMappingManager::class);
        $this->suggestedGroupsRecommendationsAlgorithm ??= new SuggestedGroupsRecommendationsAlgorithm();
        $this->logger ??= Di::_()->get('Logger');
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
        if ($this->type === 'group') {
            $groups = $this->suggestedGroupsRecommendationsAlgorithm->setUser($this->user)->getRecommendations([
                'limit' => (int) ($opts['limit'] ?? 12),
                'offset' => (int) ($opts['offset'] ?? 0),
            ]);
            if (!$groups->count()) {
                $groups = new Response($this->getDefaultTagBasedSuggestions('group'));
            }

            $groups = $groups->map(function (Suggestion $suggestion): ?Suggestion {
                $entity = $suggestion->getEntity() ?: $this->entitiesBuilder->single($suggestion->getEntityGuid());

                if (!$entity) {
                    $this->logger->warning("{$suggestion->getEntityGuid()} suggested group not found");
                    return null;
                }

                $suggestion->setEntity($entity);
                return $suggestion;
            });
    
            // Remove missing entities
            $groups = $groups->filter(function ($suggestion) {
                return $suggestion && $suggestion->getEntity();
            });

            return $groups;
        }

        $opts = array_merge([
            'limit' => 12,
            'paging-token' => '',
            'type' => $this->type,
        ], $opts);

        if ($this->user && $this->isNearSubscriptionRateLimit()) {
            return new Response([]);
        }

        $opts['limit'] = $opts['limit'] * 3; // To prevent removed channels or closed groups

        // if user set
        if ($this->user) {
            $opts['user_guid'] = $this->user->getGuid();

            // if user has more than 1 subscription.
            if ($this->subscriptionsManager->setSubscriber($this->user)
                ->getSubscriptionsCount() > 1
            ) {
                $response = $this->repository->getList($opts);

                // fallback incase no users come back.
                if ($response->count() < 1) {
                    $response = $this->getFallbackSuggested($opts);
                }
            } else {
                // else if a user has 1 or less subscriptions.
                $opts['user_guid'] = null; // unset user_guid so we can recommend defaults.
                $response = $this->getFallbackSuggested($opts);
            }
        } else {
            // if no user set - use fallback.
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
            if (!empty($entity->getNsfw())) {
                return null;
            }

            $blockEntry = (new Block\BlockEntry())
                ->setActor($this->user)
                ->setSubject($entity);
            if ($this->blockManager->hasBlocked($blockEntry) || $this->blockManager->isBlocked($blockEntry)) {
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

    private function getFallbackSuggested($opts = []): Response
    {
        $opts = array_merge([
            'user_guid' => $this->user ? $this->user->getGuid() : '',
            'type' => 'user',
        ], $opts);

        $recommendationsUserGuid = $this->config->get('default_recommendations_user') ?? '100000000000000519';

        $users = $this->subscriptionsManager->getList([
            'guid' => (string) $recommendationsUserGuid,
            'type' => 'subscriptions',
            'hydrate' => false,
            'limit' => 500,
        ]);

        $opts['user_guids'] = array_map(function ($user) {
            return $user;
        }, $users->toArray());

        $response = $this->repository->getList($opts);
        
        if ($defaultTagBasedSuggestions = $this->getDefaultTagBasedSuggestions($opts['type'])) {
            $response->prependToArray($defaultTagBasedSuggestions);
        }
        
        return $response;
    }

    /**
     * Get default suggestions based upon a users tags.
     * @param string $entityType - type of entity to get suggestions of.
     * @return array suggestions.
     */
    private function getDefaultTagBasedSuggestions(string $entityType = 'group'): array
    {
        try {
            return $this->defaultTagMappingManager->getSuggestions(
                user: $this->user,
                entityType: $entityType
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            return [];
        }
    }

    /**
     * Returns the smallest rate limit remaining attempts based on period.
     * @return bool
     */
    private function isNearSubscriptionRateLimit()
    {
        $remainingAttempts = $this->interactionsLimiter->getRemainingAttempts((string) $this->user->getGuid(), 'subscribe');
        return $remainingAttempts < 10;
    }
}
