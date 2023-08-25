<?php

namespace Spec\Minds\Core\Recommendations\Injectors;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Log\Logger;
use Minds\Core\Recommendations\Injectors\BoostSuggestionInjector;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class BoostSuggestionInjectorSpec extends ObjectBehavior
{
    private Collaborator $boostManager;
    private Collaborator $logger;

    public function let(
        BoostManager $boostManager,
        Logger $logger
    ) {
        $this->boostManager = $boostManager;
        $this->logger = $logger;
        $this->beConstructedWith($this->boostManager, $this->logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostSuggestionInjector::class);
    }

    public function it_should_inject_a_boost_for_a_user(
        Response $response,
        User $targetUser,
        User $suggestedUser1,
        User $suggestedUser2,
        User $suggestedUser3,
        Response $boostRepoResponse,
        Boost $boostedUser,
        User $boostedUserEntity
    ): void {
        $response->toArray()
            ->shouldBeCalled()
            ->willReturn([
                $suggestedUser1,
                $suggestedUser2,
                $suggestedUser3
            ]);

        $targetUser->getBoostRating()
            ->shouldBeCalled()
            ->willReturn(BoostTargetAudiences::SAFE);

        $boostedUser->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $boostedUserEntity->getType()
            ->shouldBeCalled()
            ->willReturn('user');

        $boostedUser->getEntity()
            ->shouldBeCalled()
            ->willReturn($boostedUserEntity);

        $boostRepoResponse->first()
            ->shouldBeCalled()
            ->willReturn($boostedUser);

        $this->boostManager->shouldShowBoosts($targetUser)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->boostManager->getBoosts(
            1,
            0,
            BoostStatus::APPROVED,
            false,
            null,
            true,
            BoostTargetAudiences::SAFE,
            BoostTargetLocation::SIDEBAR,
            null,
            null
        )
            ->shouldBeCalled()
            ->willReturn($boostRepoResponse);

        $boostedUser->setEntity(Argument::that(function ($entity) {
            return $entity instanceof User && $entity->exportCounts;
        }))
            ->shouldBeCalled();

        $this->inject($response, $targetUser, 1);
    }

    public function it_should_inject_a_boost_for_a_group(
        Response $response,
        User $targetUser,
        Group $suggestedGroup1,
        Group $suggestedGroup2,
        Group $suggestedGroup3,
        Response $boostRepoResponse,
        Boost $boostedGroup,
        Group $boostedGroupEntity
    ): void {
        $response->toArray()
            ->shouldBeCalled()
            ->willReturn([
                $suggestedGroup1,
                $suggestedGroup2,
                $suggestedGroup3
            ]);

        $targetUser->getBoostRating()
            ->shouldBeCalled()
            ->willReturn(BoostTargetAudiences::SAFE);

        $boostedGroup->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $boostedGroupEntity->getType()
            ->shouldBeCalled()
            ->willReturn('group');

        $boostedGroup->getEntity()
            ->shouldBeCalled()
            ->willReturn($boostedGroupEntity);

        $boostRepoResponse->first()
            ->shouldBeCalled()
            ->willReturn($boostedGroup);

        $this->boostManager->shouldShowBoosts($targetUser)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->boostManager->getBoosts(
            1,
            0,
            BoostStatus::APPROVED,
            false,
            null,
            true,
            BoostTargetAudiences::SAFE,
            BoostTargetLocation::SIDEBAR,
            null,
            null
        )
            ->shouldBeCalled()
            ->willReturn($boostRepoResponse);

        $boostedGroup->setEntity(Argument::any())
            ->shouldNotBeCalled();

        $this->inject($response, $targetUser, 1);
    }

    public function it_should_NOT_inject_a_null_boost(
        Response $response,
        User $targetUser,
        User $suggestedUser1,
        User $suggestedUser2,
        User $suggestedUser3,
        Response $boostRepoResponse,
        Boost $boostedUser,
        User $boostedUserEntity
    ): void {
        $response->toArray()
            ->shouldBeCalled()
            ->willReturn([
                $suggestedUser1,
                $suggestedUser2,
                $suggestedUser3
            ]);

        $targetUser->getBoostRating()
            ->shouldBeCalled()
            ->willReturn(BoostTargetAudiences::SAFE);

        $boostedUser->getGuid()
            ->shouldNotBeCalled();

        $boostedUserEntity->getType()
            ->shouldNotBeCalled();

        $boostedUser->getEntity()
            ->shouldNotBeCalled();

        $boostRepoResponse->first()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->boostManager->shouldShowBoosts($targetUser)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->boostManager->getBoosts(
            1,
            0,
            BoostStatus::APPROVED,
            false,
            null,
            true,
            BoostTargetAudiences::SAFE,
            BoostTargetLocation::SIDEBAR,
            null,
            null
        )
            ->shouldBeCalled()
            ->willReturn($boostRepoResponse);

        $this->inject($response, $targetUser, 1);
    }

    public function it_should_NOT_inject_a_boost_if_boosts_should_not_be_shown_for_user(
        Response $response,
        User $targetUser,
        Response $boostRepoResponse
    ): void {
        $this->boostManager->shouldShowBoosts($targetUser)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->boostManager->getBoosts(
            1,
            0,
            BoostStatus::APPROVED,
            false,
            null,
            true,
            BoostTargetAudiences::SAFE,
            BoostTargetLocation::SIDEBAR,
            null,
            null
        )
            ->shouldNotBeCalled()
            ->willReturn($boostRepoResponse);

        $this->inject($response, $targetUser, 1);
    }
}
