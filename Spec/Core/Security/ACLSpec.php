<?php

namespace Spec\Minds\Core\Security;

use PhpSpec\ObjectBehavior;
use Minds\Core;
use Minds\Core\Config;
use Minds\Entities\User;
use Minds\Entities\Entity;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;

class ACLSpec extends ObjectBehavior
{
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function let(EntitiesBuilder $entitiesBuilder, Config $config)
    {
        $this->entitiesBuilder = $entitiesBuilder;
        $config->get('normalize_entities')
            ->willReturn(true);

        $this->beConstructedWith($entitiesBuilder, null, $config);
    }

    public function mock_session($on = true)
    {
        if ($on) {
            $user = new User;
            $user->guid = 123;
            $user->username = 'minds';
        } else {
            $user = null;
        }
        Core\Session::setUser($user);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Security\ACL');
    }

    public function it_should_allow_read_of_public_entities(Entity $entity, User $user)
    {
        $user->getType()->willReturn('user');
        $user->get('type')->willReturn('user');
        $user->get('guid')->willReturn(123);
        $user->get('access_id')->willReturn(2);
        $user->get('owner_guid')->willReturn(0);
        $user->getOwnerGUID()->willReturn(0);
        $user->get('container_guid')->willReturn(0);
        $user->getSpam()->willReturn(false);
        $user->getDeleted()->willReturn(false);
        $user->isBanned()->willReturn(false);
        $user->isEnabled()->willReturn(true);
        
        $this->entitiesBuilder->single(123, [ 'cache' => true, 'cacheTtl' => 604800 ])
            ->shouldBeCalled()
            ->willReturn($user);

        $entity->getType()->willReturn('object');
        $entity->get('access_id')->willReturn(2);
        $entity->get('container_guid')->willReturn(123);
        $entity->get('owner_guid')->willReturn(123);
        $entity->getOwnerGUID()->wilLReturn(123);
        $entity->get('type')->willReturn('activity');
        $this->read($entity)->shouldReturn(true);
    }

    public function it_should_not_allow_posts_from_bad_users(Entity $entity, User $user)
    {
        $user->get('type')->willReturn('user');
        $user->get('guid')->willReturn(123);
        $user->get('access_id')->willReturn(2);
        $user->get('owner_guid')->willReturn(0);
        $user->getOwnerGUID()->willReturn(0);
        $user->get('container_guid')->willReturn(0);
        $user->get('username')->willReturn('banneduser');
        $user->getSpam()->willReturn(false);
        $user->getDeleted()->willReturn(false);
        $user->isBanned()->willReturn(true);
        $user->isEnabled()->willReturn(true);
        
        $this->entitiesBuilder->single(123, [ 'cache' => true, 'cacheTtl' => 604800 ])
            ->shouldBeCalled()
            ->willReturn($user);

        $entity->getType()->willReturn('object');
        $entity->get('guid')->willReturn(1);
        $entity->get('access_id')->willReturn(2);
        $entity->get('container_guid')->willReturn(123);
        $entity->get('owner_guid')->willReturn(123);
        $entity->getOwnerGUID()->wilLReturn(123);
        $entity->get('type')->willReturn('activity');
        $this->read($entity)->shouldReturn(false);
    }

    public function it_should_not_allow_read_of_private_entities(Entity $entity, User $user)
    {
        $user->getType()->willReturn('user');
        $user->get('type')->willReturn('user');
        $user->get('guid')->willReturn(123);
        $user->get('access_id')->willReturn(2);
        $user->get('owner_guid')->willReturn(0);
        $user->getOwnerGUID()->willReturn(0);
        $user->get('container_guid')->willReturn(0);
        $user->getSpam()->willReturn(false);
        $user->getDeleted()->willReturn(false);
        $user->isBanned()->willReturn(false);
        $user->isEnabled()->willReturn(true);
        
        $this->entitiesBuilder->single(123, [ 'cache' => true, 'cacheTtl' => 604800 ])
            ->shouldBeCalled()
            ->willReturn($user);
    
        $entity->getType()->willReturn('specy');
        $entity->get('access_id')->willReturn(0);
        $entity->get('owner_guid')->willReturn(123);
        $entity->getOwnerGUID()->wilLReturn(123);
        $entity->get('type')->willReturn('activity');
        $this->read($entity)->shouldReturn(false);
    }

    public function it_should_trigger_acl_read_event()
    {
        $activity = new Activity();

        $this->mock_session(true);

        Core\Events\Dispatcher::register('acl:read', 'all', function ($event) {
            $event->setResponse(true);
        });

        $this->read($activity)->shouldReturn(true);
        $this->mock_session(false);
    }

    public function it_should_not_allow_write_for_logged_out_users(Entity $entity)
    {
        $this->write($entity)->shouldReturn(false);
    }

    public function it_should_not_allow_write_for_none_owned_entities(Entity $entity)
    {
        $this->mock_session(true);

        $this->write($entity)->shouldReturn(false);
        $this->mock_session(false);
    }

    public function it_should_allow_write_for_own_entities(Entity $entity)
    {
        $this->mock_session(true);

        $entity->get('owner_guid')
            ->willReturn(123);
        
        $entity->get('container_guid')
            ->willReturn(123);

        $this->write($entity)->shouldReturn(true);
        $this->mock_session(false);
    }

    public function it_should_trigger_acl_write_event()
    {
        $activity = new Activity();

        $this->mock_session(true);

        Core\Events\Dispatcher::register('acl:write', 'all', function ($event) {
            $event->setResponse(true);
        });

        $this->read($activity)->shouldReturn(true);
        $this->mock_session(false);
    }

    public function it_should_not_allow_interaction_for_logged_out_users(Entity $entity)
    {
        $this->interact($entity)->shouldReturn(false);
    }

    public function it_should_allow_interaction(Entity $entity)
    {
        $this->mock_session(true);

        $this->interact($entity)->shouldReturn(true);
        $this->mock_session(false);
    }

    public function it_should_return_false_on_acl_interact_event(Entity $entity)
    {
        $this->mock_session(true);

        Core\Events\Dispatcher::register('acl:interact', 'all', function ($event) {
            $event->setResponse(false);
        });

        $this->interact($entity)->shouldReturn(false);
        $this->mock_session(false);
    }

    public function it_should_ignore(Entity $entity)
    {
        $this->setIgnore(true);
        $this->read($entity)->shouldReturn(true);
        $this->write($entity)->shouldReturn(true);
        $this->setIgnore(false);
    }
}
