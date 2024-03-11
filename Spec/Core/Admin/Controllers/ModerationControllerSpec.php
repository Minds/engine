<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Admin\Controllers;

use Minds\Core\Admin\Controllers\ModerationController;
use Minds\Core\Admin\Services\ModerationService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ModerationControllerSpec extends ObjectBehavior
{
    private Collaborator $moderationService;

    public function let(ModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
        $this->beConstructedWith($this->moderationService);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(ModerationController::class);
    }

    public function it_should_pass_ban_user_request_to_service(): void
    {
        $subjectGuid = '123';
        $banState = true;
        $this->moderationService->setUserBanState($subjectGuid, $banState)->willReturn(true);
        $this->setUserBanState($subjectGuid, $banState)->shouldReturn(true);
    }

    public function it_should_pass_unban_user_request_to_service(): void
    {
        $subjectGuid = '123';
        $banState = false;
        $this->moderationService->setUserBanState($subjectGuid, $banState)->willReturn(true);
        $this->setUserBanState($subjectGuid, $banState)->shouldReturn(true);
    }

    public function it_should_pass_delete_entity_request_to_service(): void
    {
        $entityUrn = 'urn:activity:123';
        $this->moderationService->deleteEntity($entityUrn)->willReturn(true);
        $this->deleteEntity($entityUrn)->shouldReturn(true);
    }
}
