<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Controllers\SiteMembershipOnlyModeController;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipOnlyModeService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class SiteMembershipOnlyModeControllerSpec extends ObjectBehavior
{
    private Collaborator $siteMembershipOnlyModeServiceMock;

    public function let(SiteMembershipOnlyModeService $siteMembershipOnlyModeServiceMock)
    {
        $this->beConstructedWith($siteMembershipOnlyModeServiceMock);
        $this->siteMembershipOnlyModeServiceMock = $siteMembershipOnlyModeServiceMock;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SiteMembershipOnlyModeController::class);
    }

    public function it_should_determine_whether_to_show_membership_gate(User $user): void
    {
        $this->siteMembershipOnlyModeServiceMock->shouldRestrictAccess($user)
            ->willReturn(true);

        $response = $this->callOnWrappedObject('shouldShowMembershipGate', [$user]);

        $response->shouldBe(true);
    }

    public function it_should_determine_whether_to_not_show_membership_gate(User $user): void
    {
        $this->siteMembershipOnlyModeServiceMock->shouldRestrictAccess($user)
            ->willReturn(false);

        $response = $this->callOnWrappedObject('shouldShowMembershipGate', [$user]);

        $response->shouldBe(false);
    }
}
