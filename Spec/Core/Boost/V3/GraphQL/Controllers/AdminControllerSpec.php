<?php

namespace Spec\Minds\Core\Boost\V3\GraphQL\Controllers;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Boost\V3\GraphQL\Controllers\AdminController;
use Minds\Core\Guid;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class AdminControllerSpec extends ObjectBehavior
{
    /** @var BoostManager */
    protected $boostManagerMock;

    public function let(BoostManager $boostManagerMock)
    {
        $this->beConstructedWith($boostManagerMock);
        $this->boostManagerMock = $boostManagerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AdminController::class);
    }

    public function it_should_cancel_boosts(ServerRequest $request)
    {
        $entityGuid = Guid::build();
        
        $this->boostManagerMock->cancelByEntityGuid(
            entityGuid: (string) $entityGuid,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        )
            ->willReturn(true);

        $this->adminCancelBoosts($entityGuid);
    }
}
