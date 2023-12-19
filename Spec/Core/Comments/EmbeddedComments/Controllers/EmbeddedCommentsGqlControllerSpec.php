<?php

namespace Spec\Minds\Core\Comments\EmbeddedComments\Controllers;

use Minds\Core\Comments\Comment;
use Minds\Core\Comments\EmbeddedComments\Controllers\EmbeddedCommentsGqlController;
use Minds\Core\Comments\EmbeddedComments\Models\EmbeddedCommentsSettings;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsActivityService;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsCommentService;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsSettingsService;
use Minds\Core\Comments\EmbeddedComments\Types\EmbeddedCommentsConnection;
use Minds\Core\Comments\GraphQL\Types\CommentEdge;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class EmbeddedCommentsGqlControllerSpec extends ObjectBehavior
{
    protected Collaborator $embeddedCommentsActivityServiceMock;
    protected Collaborator $embeddedCommentsCommentServiceMock;
    protected Collaborator $embeddedCommentsSettingsServiceMock;

    public function let(
        EmbeddedCommentsActivityService $embeddedCommentsActivityServiceMock,
        EmbeddedCommentsCommentService $embeddedCommentsCommentServiceMock,
        EmbeddedCommentsSettingsService $embeddedCommentsSettingsServiceMock,
    ) {
        $this->beConstructedWith($embeddedCommentsActivityServiceMock, $embeddedCommentsCommentServiceMock, $embeddedCommentsSettingsServiceMock);

        $this->embeddedCommentsActivityServiceMock = $embeddedCommentsActivityServiceMock;
        $this->embeddedCommentsCommentServiceMock = $embeddedCommentsCommentServiceMock;
        $this->embeddedCommentsSettingsServiceMock = $embeddedCommentsSettingsServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmbeddedCommentsGqlController::class);
    }

    public function it_should_return_comments_connection(Activity $activityMock)
    {
        $activityMock->getUrl()
            ->willReturn('https://test/path');

        $this->embeddedCommentsActivityServiceMock->withOwnerGuid(1)
            ->shouldBeCalled()
            ->willReturn($this->embeddedCommentsActivityServiceMock);

        $this->embeddedCommentsActivityServiceMock->withUrl('https://phpspec.test/fake-url')
            ->shouldBeCalled()
            ->willReturn($this->embeddedCommentsActivityServiceMock);

        $this->embeddedCommentsActivityServiceMock->getActivityFromUrl(true)
            ->shouldBeCalled()
            ->willReturn($activityMock);

        $this->embeddedCommentsCommentServiceMock->getComments($activityMock, '0:0:0', 12, null, null, false, 0)
            ->willYield([
                new Comment(),
            ]);

        $connection  = $this->getEmbeddedComments(1, 'https://phpspec.test/fake-url');
        $connection->shouldBeAnInstanceOf(EmbeddedCommentsConnection::class);
        $connection->getEdges()->shouldHaveCount(1);
        $connection->getTotalCount()->shouldReturn(0);
        $connection->getActivityUrl()->shouldBe('https://test/path');
        $connection->getPageInfo()->getHasNextPage()->shouldBe(false);
        $connection->getPageInfo()->getHasPreviousPage()->shouldBe(false);
    }

    public function it_should_create_a_comment()
    {
        $activity = new Activity();
        $loggedInUser = new User();

        $this->embeddedCommentsActivityServiceMock->withOwnerGuid(1)
            ->shouldBeCalled()
            ->willReturn($this->embeddedCommentsActivityServiceMock);

        $this->embeddedCommentsActivityServiceMock->withUrl('https://phpspec.test/fake-url')
            ->shouldBeCalled()
            ->willReturn($this->embeddedCommentsActivityServiceMock);

        $this->embeddedCommentsActivityServiceMock->getActivityFromUrl(false)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->embeddedCommentsCommentServiceMock->createComment($activity, '0:0:0', $loggedInUser, 'message')
            ->willReturn(new Comment);

        $loggedInUser = new User();

        $response = $this->createEmbeddedComment(1, 'https://phpspec.test/fake-url', '0:0:0', 'message', $loggedInUser);
        $response->shouldBeAnInstanceOf(CommentEdge::class);
    }

    public function it_should_not_allow_comments_if_disabled()
    {
        $activity = new Activity();
        $loggedInUser = new User();

        $activity->setAllowComments(false);

        $this->embeddedCommentsActivityServiceMock->withOwnerGuid(1)
            ->shouldBeCalled()
            ->willReturn($this->embeddedCommentsActivityServiceMock);

        $this->embeddedCommentsActivityServiceMock->withUrl('https://phpspec.test/fake-url')
            ->shouldBeCalled()
            ->willReturn($this->embeddedCommentsActivityServiceMock);

        $this->embeddedCommentsActivityServiceMock->getActivityFromUrl(false)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->embeddedCommentsCommentServiceMock->createComment($activity, '0:0:0', $loggedInUser, 'message')
            ->willReturn(new Comment);

        $loggedInUser = new User();

        $this->shouldThrow(ForbiddenException::class)->duringCreateEmbeddedComment(1, 'https://phpspec.test/fake-url', '0:0:0', 'message', $loggedInUser);
    }

    public function it_should_return_settings()
    {
        $loggedInUser = new User();
        $loggedInUser->guid = 1;

        $settings = new EmbeddedCommentsSettings(1, 'phpspec.local', '\/(.*)', true);

        $this->embeddedCommentsSettingsServiceMock->getSettings(1, false)
            ->shouldBeCalled()
            ->willReturn($settings);

        $this->getEmbeddedCommentsSettings($loggedInUser)->shouldBe($settings);
    }

    public function it_should_set_settings()
    {
        $loggedInUser = new User();

        $this->embeddedCommentsSettingsServiceMock->setSettings(Argument::type(EmbeddedCommentsSettings::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setEmbeddedCommentsSettings('phpspec.local', '\/(.*)', true, $loggedInUser)
            ->shouldBeAnInstanceOf(EmbeddedCommentsSettings::class);
    }
}
