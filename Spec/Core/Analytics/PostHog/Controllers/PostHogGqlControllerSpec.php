<?php

namespace Spec\Minds\Core\Analytics\PostHog\Controllers;

use Minds\Core\Analytics\PostHog\Controllers\PostHogGqlController;
use Minds\Core\Analytics\PostHog\Models\PostHogPerson;
use Minds\Core\Analytics\PostHog\PostHogPersonService;
use Minds\Core\Guid;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class PostHogGqlControllerSpec extends ObjectBehavior
{
    private Collaborator $postHogPersonServiceMock;

    public function let(PostHogPersonService $postHogPersonServiceMock)
    {
        $this->beConstructedWith($postHogPersonServiceMock);

        $this->postHogPersonServiceMock = $postHogPersonServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PostHogGqlController::class);
    }

    public function it_should_return_posthog_person(User $userMock)
    {
        $guid = Guid::build();
        $userMock->getGuid()
            ->willReturn($guid);

        $this->postHogPersonServiceMock->getPerson($guid)
            ->willReturn(new PostHogPerson($guid));

        $response = $this->getPostHogPerson($userMock);
        $response->id->shouldBe($guid);
    }

    public function it_should_delete_posthog_person(User $userMock)
    {
        $guid = Guid::build();
        $userMock->getGuid()
            ->willReturn($guid);

        $this->postHogPersonServiceMock->deletePerson($guid)
            ->willReturn(true);

        $this->deletePostHogPerson($userMock)->shouldBe(true);
    }
}
