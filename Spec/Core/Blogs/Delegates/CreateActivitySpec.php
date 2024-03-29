<?php

namespace Spec\Minds\Core\Blogs\Delegates;

use Minds\Core\Blogs\Blog;
use Minds\Core\Data\Call;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class CreateActivitySpec extends ObjectBehavior
{
    /** @var Save */
    protected $saveAction;

    /** @var Call */
    protected $db;

    protected Collaborator $entitiesBuilder;

    public function let(
        Save $saveAction,
        Call $db,
        EntitiesBuilder $entitiesBuilder,
    ) {
        $this->beConstructedWith($saveAction, $db, $entitiesBuilder);
        $this->saveAction = $saveAction;
        $this->db = $db;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Blogs\Delegates\CreateActivity');
    }

    public function it_should_save_when_no_activity(
        Blog $blog,
        User $user
    ) {
        $blog->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $this->entitiesBuilder->single(456)->willReturn($user);

        $blog->getTitle()
            ->shouldBeCalled()
            ->willReturn('blog title');

        $blog->getBody()
            ->shouldBeCalled()
            ->willReturn('<p>blog body</p>');

        $blog->getUrl()
            ->shouldBeCalled()
            ->willReturn('http://phpspec/blog/5000');

        $blog->getIconUrl()
            ->shouldBeCalled()
            ->willReturn('http://phpspec/icon.spec.ext');
        
        $blog->isMature()
            ->shouldBeCalled()
            ->willReturn(false);

        $blog->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $blog->getWireThreshold()
            ->shouldBeCalled()
            ->willReturn(null);

        $blog->isPaywall()
            ->shouldBeCalled()
            ->willReturn(false);

        $blog->getGuid()
            ->shouldBeCalled()
            ->willReturn(9999);

        $user->export()
            ->shouldBeCalled()
            ->willReturn([]);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $blog->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(9999);

        $blog->getAccessId()
            ->shouldBeCalled()
            ->willReturn(2);

        $this->db->getRow("activity:entitylink:9999")
            ->shouldBeCalled()
            ->willReturn([]);

        $this->saveAction->setEntity(Argument::type(Activity::class))
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->save($blog)
            ->shouldReturn(true);
    }

    public function it_should_save_when_previous_activity(
        Blog $blog
    ) {
        $blog->getGuid()
            ->shouldBeCalled()
            ->willReturn(9999);

        $blog->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(9999);

        $this->db->getRow("activity:entitylink:9999")
            ->shouldBeCalled()
            ->willReturn(['activity1']);

        $this->saveAction->setEntity(Argument::type(Activity::class))
        ->shouldBeCalled()
        ->willReturn($this->saveAction);

        $this->saveAction->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->save($blog)
            ->shouldReturn(true);
    }
}
