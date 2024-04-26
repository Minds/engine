<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Search\Helpers;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Search\Helpers\DirectMatchInjector;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class DirectMatchInjectorSpec extends ObjectBehavior
{
    protected $entitiesBuilder;

    public function let(
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->beConstructedWith($entitiesBuilder);
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldBeAnInstanceOf(DirectMatchInjector::class);
    }

    public function it_should_inject_direct_user_match_when_no_match_is_found_in_passed_array(
        User $userMatch
    ): void {
        $query = 'username1';
        $entities = [
            ['username' => 'username2'],
            ['username' => 'username3'],
        ];

        $userMatch->export()->willReturn(['username' => 'username1']);

        $this->entitiesBuilder->getByUserByIndex($query)
            ->shouldBeCalled()
            ->willReturn($userMatch);

        $this->injectDirectUserMatch($entities, $query, true)->shouldReturn([
            ['username' => 'username1'],
            ['username' => 'username2'],
            ['username' => 'username3'],
        ]);
    }

    public function it_should_inject_direct_user_match_when_no_match_is_found_in_passed_array_and_match_is_nsfw_when_including_nsfw(
        User $userMatch
    ): void {
        $query = 'username1';
        $entities = [
            ['username' => 'username2'],
            ['username' => 'username3'],
        ];

        $userMatch->getNsfw()->willReturn([]);
        $userMatch->export()->willReturn(['username' => 'username1']);

        $this->entitiesBuilder->getByUserByIndex($query)
            ->shouldBeCalled()
            ->willReturn($userMatch);

        $this->injectDirectUserMatch($entities, $query, true)->shouldReturn([
            ['username' => 'username1'],
            ['username' => 'username2'],
            ['username' => 'username3'],
        ]);
    }

    public function it_should_NOT_inject_direct_user_match_when_no_match_is_found_in_passed_array_but_match_is_nsfw_when_not_including_nsfw(
        User $userMatch
    ): void {
        $query = 'username1';
        $entities = [
            ['username' => 'username2'],
            ['username' => 'username3'],
        ];

        $userMatch->getNsfw()->willReturn([1]);
        $userMatch->export()->willReturn(['username' => 'username1']);

        $this->entitiesBuilder->getByUserByIndex($query)
            ->shouldBeCalled()
            ->willReturn($userMatch);

        $this->injectDirectUserMatch($entities, $query, false)->shouldReturn([
            ['username' => 'username2'],
            ['username' => 'username3'],
        ]);
    }

    public function it_should_not_inject_direct_user_match_when_no_match_is_found_in_passed_array_and_no_direct_entity_is_resolved(): void
    {
        $query = 'username1';
        $entities = [
            ['username' => 'username2'],
            ['username' => 'username3'],
        ];

        $this->entitiesBuilder->getByUserByIndex($query)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->injectDirectUserMatch($entities, $query, true)->shouldReturn([
            ['username' => 'username2'],
            ['username' => 'username3'],
        ]);
    }

    public function it_should_move_existing_match_to_top_if_one_already_exists(): void
    {
        $query = 'username1';
        $entities = [
            ['username' => 'username2'],
            ['username' => 'username1'],
        ];

        $this->injectDirectUserMatch($entities, $query, true)->shouldReturn([
            ['username' => 'username1'],
            ['username' => 'username2'],
        ]);
    }

    public function it_should_do_nothing_if_existing_match_exists_and_is_at_top(): void
    {
        $query = 'username1';
        $entities = [
            ['username' => 'username1'],
            ['username' => 'username2'],
        ];

        $this->injectDirectUserMatch($entities, $query, true)->shouldReturn([
            ['username' => 'username1'],
            ['username' => 'username2'],
        ]);
    }
}
