<?php

namespace Spec\Minds\Core\Security\Block;

use Minds\Core\Security\Block\Manager;
use Minds\Core\Security\Block\Repository;
use Minds\Core\Security\Block\BlockEntry;
use Minds\Core\Security\Block\BlockListOpts;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Common\Repository\Response;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var PsrWrapper */
    protected $cache;

    public function let(Repository $repository, PsrWrapper $cache)
    {
        $this->beConstructedWith($repository, $cache);
        $this->repository = $repository;
        $this->cache = $cache;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_a_list_subjects_actor_has_blocked()
    {
        $opts = new BlockListOpts();
        $opts->setUserGuid(123);

        $this->repository->getList($opts)
            ->willReturn(new Response([ 456 ]));

        $this->getList($opts);
    }

    public function it_should_return_a_list_subjects_actor_has_blocked_from_cache()
    {
        $opts = new BlockListOpts();
        $opts->setUserGuid(123);

        $this->repository->getList(Argument::any())
            ->shouldNotBeCalled();

        $this->cache->get('acl:block:list:123')
            ->willReturn(serialize(new Response([ 456 ])));

        //

        $this->getList($opts);
    }

    public function it_should_return_that_actor_is_blocked_by_subject()
    {
        $blockEntry = (new BlockEntry())
            ->setActorGuid(123)
            ->setSubjectGuid(456);

        $this->repository->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === 456;
        }))
            ->willReturn(new Response([ 123 ]));

        //

        $this->isBlocked($blockEntry)
            ->shouldBe(true);
    }

    public function it_should_return_that_actor_is_not_blocked_by_subject()
    {
        $blockEntry = (new BlockEntry())
            ->setActorGuid(123)
            ->setSubjectGuid(456);

        $this->repository->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === 456;
        }))
            ->willReturn(new Response([ 789 ]));

        //

        $this->isBlocked($blockEntry)
            ->shouldBe(false);
    }

    public function it_should_return_that_subject_is_blocked_by_actor()
    {
        $blockEntry = (new BlockEntry())
            ->setActorGuid(123)
            ->setSubjectGuid(456);

        $this->repository->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === 123;
        }))
            ->willReturn(new Response([ 456 ]));

        //

        $this->hasBlocked($blockEntry)
            ->shouldbe(true);
    }

    public function it_should_return_that_subject_is_not_blocked_by_actor()
    {
        $blockEntry = (new BlockEntry())
            ->setActorGuid(123)
            ->setSubjectGuid(456);

        $this->repository->getList(Argument::that(function ($opts) {
            return $opts->getUserGuid() === 123;
        }))
            ->willReturn(new Response([ 789 ]));

        //

        $this->hasBlocked($blockEntry)
            ->shouldbe(false);
    }

    public function it_should_add_subject_to_actors_block_list()
    {
        $blockEntry = (new BlockEntry())
            ->setActorGuid(123)
            ->setSubjectGuid(456);

        $this->repository->add($blockEntry)
            ->willReturn(true);

        // Cache should be cleaned up
        $this->cache->delete('acl:block:list:123')
            ->shouldBeCalled();

        //
        
        $this->add($blockEntry)->shouldBe(true);
    }

    public function it_should_remove_subject_from_actors_block_list()
    {
        $blockEntry = (new BlockEntry())
            ->setActorGuid(123)
            ->setSubjectGuid(456);

        $this->repository->delete($blockEntry)
            ->willReturn(true);

        // Cache should be cleaned up
        $this->cache->delete('acl:block:list:123')
            ->shouldBeCalled();

        //
        
        $this->delete($blockEntry)->shouldBe(true);
    }
}
