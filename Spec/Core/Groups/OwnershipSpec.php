<?php

namespace Spec\Minds\Core\Groups;

use Minds\Common\Repository\Response;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\Membership;
use Minds\Core\Groups\Ownership;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OwnershipSpec extends ObjectBehavior
{
    /** @var Membership */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(
        Membership $manager,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->manager = $manager;
        $this->entitiesBuilder = $entitiesBuilder;

        $this->beConstructedWith($manager, $entitiesBuilder);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Ownership::class);
    }

    public function it_should_fetch(
        Group $group1,
        Group $group2,
        Group $group3
    ) {
        $this->manager->getGroupGuidsByMember([
            'user_guid' => '1000',
            'limit' => 20,
        ])
            ->shouldBeCalled()
            ->willReturn([ '5000', '5001', '5002', '5003', '5004', '5005' ]);

        $this->entitiesBuilder->get([
            'guids' => [ '5001', '5002', '5003' ]
        ])
            ->shouldBeCalled()
            ->willReturn([
                $group1,
                $group2,
                $group3,
            ]);

        $group1->isPublic()
            ->willReturn(true);

        $group1->isOwner(Argument::that(function (User $user) {
            return $user->get('guid') == '1000';
        }))
            ->willReturn(true);

        $group1->getMembersCount()
            ->willReturn(100);

        $group2->isPublic()
            ->willReturn(false);

        $group2->isOwner(Argument::that(function (User $user) {
            return $user->get('guid') == '1000';
        }))
            ->willReturn(true);

        $group2->getMembersCount()
            ->willReturn(200);

        $group3->isPublic()
            ->willReturn(true);

        $group3->isOwner(Argument::that(function (User $user) {
            return $user->get('guid') == '1000';
        }))
            ->willReturn(true);

        $group3->getMembersCount()
            ->willReturn(300);

        $this
            ->setUserGuid(1000)
            ->fetch([
                'cap' => 20,
                'offset' => 1,
                'limit' => 3,
            ])
            ->shouldBeAResponse([ $group3, $group1 ]);
    }

    public function it_should_fetch_an_empty_set()
    {
        $this->manager->getGroupGuidsByMember([
            'user_guid' => '1000',
            'limit' => 20,
        ])
            ->shouldBeCalled()
            ->willReturn([]);

        $this->entitiesBuilder->get(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setUserGuid(1000)
            ->fetch([
                'cap' => 20,
                'offset' => 1,
                'limit' => 3,
            ])
            ->shouldBeAResponse([ ]);
    }

    public function getMatchers(): array
    {
        $matchers = [];

        $matchers['beAResponse'] = function ($subject, $elements = null) {
            if (!($subject instanceof Response)) {
                throw new FailureException("Subject should be a Response");
            }

            if ($elements !== null && $elements !== $subject->toArray()) {
                throw new FailureException("Subject elements don't match");
            }

            return true;
        };

        return $matchers;
    }
}
