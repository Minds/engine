<?php

declare(strict_types=1);

namespace Minds\Core\Recommendations\Injectors;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Boost\V3\Models\BoostEntityWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Suggestions\Suggestion;
use Minds\Entities\User;

/**
 * Injects a boost suggestion into a prepared Response object.
 */
class BoostSuggestionInjector
{
    public function __construct(
        private ?BoostManager $boostManager = null,
        private ?Logger $logger = null
    ) {
        $this->boostManager ??= Di::_()->get(BoostManager::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Inject a boost suggestion into a prepared response object.
     * @param Response $response - response object to permute.
     * @param User $targetUser - user the boost is intended for.
     * @param int $index - index at which to inject the boost.
     * @return Response - permuted response.
     */
    public function inject(Response $response, User $targetUser, int $index = 1): Response
    {
        try {
            if (!$this->boostManager->shouldShowBoosts($targetUser)) {
                return $response;
            }

            $entitiesArray = $response->toArray();
            $boost = $this->getInjectableBoostSuggestion($targetUser);

            if ($boost) {
                array_splice($entitiesArray, $index, 0, [$boost]);
            }
            return new Response($entitiesArray);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return $response;
        }
    }

    /**
     * Gets in injectable boost wrapped in a suggestion.
     * @param User $targetUser - user the boost is targeted for.
     * @return Suggestion|null - suggestion containing wrapped boost.
     */
    private function getInjectableBoostSuggestion(User $targetUser): ?Suggestion
    {
        $targetAudience = $targetUser->getBoostRating() !== BoostTargetAudiences::CONTROVERSIAL ?
                BoostTargetAudiences::SAFE :
                BoostTargetAudiences::CONTROVERSIAL;

        $boost = $this->boostManager->getBoosts(
            limit: 1,
            targetStatus: BoostStatus::APPROVED,
            orderByRanking: true,
            targetAudience: $targetAudience,
            targetLocation: BoostTargetLocation::SIDEBAR
        )->first();

        if (!$boost) {
            return null;
        }

        $entity = $boost->getEntity();
        $boostedEntityType = $boost->getEntity()->getType() ?? null;

        // export subscriber / subscription counts for users.
        if ($boostedEntityType === 'user') {
            $entity->exportCounts = true;
            $boost->setEntity($entity);
        }

        return (new Suggestion())
            ->setConfidenceScore(1)
            ->setEntityGuid($boost->getGuid())
            ->setEntity(new BoostEntityWrapper($boost))
            ->setEntityType($boostedEntityType);
    }
}
