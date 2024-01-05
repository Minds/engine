<?php

namespace Spec\Minds\Core\Comments\EmbeddedComments\Services;

use Minds\Common\Repository\Response;
use Minds\Core\Comments\Comment;
use Minds\Core\Comments\Manager;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsCommentService;
use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class EmbeddedCommentsCommentServiceSpec extends ObjectBehavior
{
    private Collaborator $commentsManagerMock;
    private Collaborator $configMock;
    private Collaborator $aclMock;
    private Collaborator $loggerMock;

    public function let(
        Manager $commentsManagerMock,
        Config $configMock,
        ACL $aclMock,
        Logger $loggerMock,
    ) {
        $this->beConstructedWith($commentsManagerMock, $configMock, $aclMock, $loggerMock);
        $this->commentsManagerMock = $commentsManagerMock;
        $this->configMock = $configMock;
        $this->aclMock = $aclMock;
        $this->loggerMock = $loggerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmbeddedCommentsCommentService::class);
    }

    public function it_should_return_comments()
    {
        $activity = new Activity();
        $activity->guid = 1;

        $this->commentsManagerMock->count(1, '0:0:0')
            ->willReturn(1);

        $comment1 = new Comment();

        $this->commentsManagerMock->getList([
            'entity_guid' => 1,
            'parent_path' => '0:0:0',
            'limit' => 12,
            'offset' => null,
        ])
            ->willReturn(new Response([
                $comment1,
            ]));

        $response = $this->getComments($activity);
        $response->shouldYieldLike([$comment1]);
    }

    public function it_should_create_a_comment()
    {
        $activity = new Activity();
        $activity->guid = 1;

        $owner = new User();

        $this->commentsManagerMock->add(Argument::type(Comment::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->createComment($activity, '0:0:0', $owner, 'message body')
            ->shouldBeAnInstanceOf(Comment::class);
    }
}
