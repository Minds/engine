<?php

namespace Spec\Minds\Core\Blogs;

use Minds\Core\Blogs\Blog;
use Minds\Core\Blogs\Delegates;
use Minds\Core\Blogs\Repository;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Spam;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Delegates\PaywallReview */
    protected $paywallReview;

    /** @var Delegates\Slug */
    protected $slug;

    /** @var Delegates\Feeds */
    protected $feeds;

    /** @var Spam */
    protected $spam;

    /** @var Delegates\Search */
    protected $search;

    /** @var PropagateProperties */
    protected $propagateProperties;

    public function let(
        Repository $repository,
        Delegates\PaywallReview $paywallReview,
        Delegates\Slug $slug,
        Delegates\Feeds $feeds,
        Spam $spam,
        Delegates\Search $search,
        PropagateProperties $propagateProperties
    ) {
        $this->beConstructedWith(
            $repository,
            $paywallReview,
            $slug,
            $feeds,
            $spam,
            $search,
            $propagateProperties
        );

        $this->repository = $repository;
        $this->paywallReview = $paywallReview;
        $this->slug = $slug;
        $this->feeds = $feeds;
        $this->spam = $spam;
        $this->search = $search;
        $this->propagateProperties = $propagateProperties;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Blogs\Manager');
    }

    public function it_should_get(Blog $blog)
    {
        $this->repository->get('10000000000000000000')
            ->shouldBeCalled()
            ->willReturn($blog);

        $this
            ->get('10000000000000000000')
            ->shouldReturn($blog);
    }

    public function it_should_get_with_legacy_guid(Blog $blog)
    {
        $migratedGuid = (new \GUID())->migrate(1);

        $this->repository->get($migratedGuid)
            ->shouldBeCalled()
            ->willReturn($blog);

        $this
            ->get(1)
            ->shouldReturn($blog);
    }

    public function it_should_get_next_blog_by_owner(Blog $blog, Blog $nextBlog)
    {
        $blog->getGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $blog->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->repository->getList([
            'gt' => 5000,
            'limit' => 1,
            'user' => 1000,
            'reversed' => false,
        ])
            ->shouldBeCalled()
            ->willReturn([$nextBlog]);

        $this
            ->getNext($blog, 'owner')
            ->shouldReturn($nextBlog);
    }

    public function it_should_get_next_null_blog_by_owner(Blog $blog)
    {
        $blog->getGuid()
            ->shouldBeCalled()
            ->willReturn(5001);

        $blog->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->repository->getList([
            'gt' => 5001,
            'limit' => 1,
            'user' => 1000,
            'reversed' => false,
        ])
            ->shouldBeCalled()
            ->willReturn([]);

        $this
            ->getNext($blog, 'owner')
            ->shouldReturn(null);
    }

    public function it_should_throw_if_no_strategy_during_get_next(Blog $blog)
    {
        $this->repository->getList(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new \Exception('Unknown next strategy'))
            ->duringGetNext($blog, 'notimplemented');
    }

    public function it_should_add(Blog $blog)
    {
        $this->spam->check($blog)
            ->shouldBeCalled();

        $blog->getType()
            ->willReturn('object');

        $blog->getSubtype()
            ->willReturn('blog');

        $blog->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(9999);

        $blog->setTimeCreated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setTimeUpdated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setLastUpdated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setLastSave(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->isDeleted()
            ->shouldBeCalled()
            ->willReturn(false);

        $blog->getUrn()
            ->willReturn('urn:blog:123');

        $this->slug->generate($blog)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->add($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feeds->index($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feeds->dispatch($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->search->index($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->paywallReview->queue($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->add($blog)
            ->shouldReturn(true);
    }

    public function it_should_update(Blog $blog)
    {
        $blog->isDirty('deleted')
            ->shouldBeCalled()
            ->willReturn(true);

        $blog->isDeleted()
            ->shouldBeCalled()
            ->willReturn(false);

        $blog->setTimeUpdated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setLastUpdated(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->setLastSave(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($blog);

        $blog->getUrn()
            ->willReturn('urn:blog:123');

        $this->slug->generate($blog)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->repository->update($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->paywallReview->queue($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->search->index($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->propagateProperties->from($blog)->shouldBeCalled();
        $this
            ->update($blog)
            ->shouldReturn(true);
    }

    public function it_should_delete(Blog $blog)
    {
        $blog->getUrn()
            ->willReturn('urn:blog:123');

        $this->repository->delete($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feeds->remove($blog)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->search->prune($blog)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->delete($blog)
            ->shouldReturn(true);
    }

    public function it_should_check_for_spam(Blog $blog, Spam $spam)
    {
        $spamUrl = 'movieblog.tumblr.com';

        $blog->getType()
            ->willReturn('object');

        $blog->getSubtype()
            ->willReturn('blog');

        $this->spam->check(Argument::any())->shouldBeCalled()->willReturn(true);
        $this->add($blog);
    }
}
