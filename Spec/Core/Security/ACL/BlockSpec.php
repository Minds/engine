<?php

namespace Spec\Minds\Core\Security\ACL;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Thrift\Indexes;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Prophet;
use Minds\Core\Security\Block;
use Minds\Common\Repository\Response;
use Minds\Entities\Entity;
use Minds\Entities\User;

class BlockSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Security\ACL\Block');
    }

    public function it_should_return_legacy_block_list(Block\Manager $blockManager)
    {
        $this->beConstructedWith($blockManager);

        $blockManager->getList(Argument::any())
            ->willReturn(new Response([
                (new Block\BlockEntry())
                    ->setSubjectGuid('bar'),
                (new Block\BlockEntry())
                    ->setSubjectGuid('boo')
            ]));

        $actor = new User();
        $actor->guid = 123;
        $userGuids = $this->getBlockList($actor);
        $userGuids[0]->shouldBe('bar');
        $userGuids[1]->shouldBe('boo');
    }

    public function it_should_add_a_user_to_the_list(Block\Manager $blockManager)
    {
        $this->beConstructedWith($blockManager);
        
        $blockManager->add(Argument::that(function (Block\BlockEntry $blockEntry) {
            return $blockEntry->getActorGuid() === 'foo'
                && $blockEntry->getSubjectGuid() === 'bar';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->block("bar", "foo")->shouldReturn(true);
    }

    public function it_should_remove_a_user_from_the_list(Block\Manager $blockManager)
    {
        $this->beConstructedWith($blockManager);

        $blockManager->delete(Argument::that(function (Block\BlockEntry $blockEntry) {
            return $blockEntry->getActorGuid() === 'foo'
                && $blockEntry->getSubjectGuid() === 'bar';
        }))
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->unBlock("bar", "foo")->shouldReturn(true);
    }
}
