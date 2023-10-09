<?php

namespace Spec\Minds\Core\Entities\Actions;

use Minds\Core\Blogs\Blog;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class SaveSpec extends ObjectBehavior
{
    /** @var EventsDispatcher */
    protected $dispatcher;

    protected Collaborator $entitiesBuilderMock;
    protected Collaborator $entitiesRepositoryMock;#
    protected Collaborator $aclMock;

    public function let(
        EventsDispatcher $dispatcher,
        EntitiesBuilder $entitiesBuilderMock,
        EntitiesRepositoryInterface $entitiesRepositoryMock,
        ACL $aclMock,
    ) {
        $this->beConstructedWith($dispatcher, null, $entitiesBuilderMock, $entitiesRepositoryMock, $aclMock);
        $this->dispatcher = $dispatcher;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->entitiesRepositoryMock = $entitiesRepositoryMock;
        $this->aclMock = $aclMock;

        $this->aclMock->write(Argument::any())->willReturn(true);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Save::class);
    }

    public function it_should_save_an_entity_using_its_save_method(User $user)
    {
        $user->getGuid()
            ->willReturn(null);
            
        $user->set('guid', Argument::any())
            ->shouldBeCalled();
    
        $user->getUrn()
            ->willReturn('urn:user:123');

        $user->getType()
            ->willReturn('user');

        $user->getSubtype()
            ->willReturn(null);

        $user->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(null);

        $user->get('language')
            ->shouldBeCalled()
            ->willReturn(null);

        $user->getOwnerGuid()
            ->willReturn(null);

        $user->getContainerGuid()
            ->shouldBeCalled()
            ->willReturn(null);

        $user->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $user->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([]);

        $user->setNsfw([])
            ->shouldBeCalled();

        //
        
        $this->entitiesRepositoryMock->create($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->dispatcher->trigger('create', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entities-ops', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entity:save', Argument::any(), Argument::any(), true)
            ->willReturn(true);

        //

        $this->setEntity($user);

        $this->save()->shouldReturn(true);
    }

    public function it_should_save_an_entity_via_the_entity_save_event(Blog $blog)
    {
        $blog->getGuid()
            ->willReturn('');

        // $blog->set('guid', Argument::any())
        //     ->shouldBeCalled();

        $blog->getUrn()
            ->willReturn('urn:blog:123');

        $blog->getType()
            ->willReturn('object');

        $blog->getSubtype()
            ->willReturn('blog');
    
        $blog->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('');

        $blog->getContainerGuid()
            ->willReturn(null);

        $blog->language = null;

        $blog->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $blog->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([]);

        $blog->setNsfw([])->shouldBeCalled();

        $this->dispatcher->trigger('create', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entities-ops', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entity:save', 'object:blog', ['entity' => $blog], false)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesRepositoryMock->create($blog)->willReturn(false);

        $this->setEntity($blog);
        $this->save()->shouldReturn(true);
    }

    public function it_should_save_an_entity_using_its_save_method_with_NSFW_from_owner(Activity $activity, User $owner)
    {
        $nsfw = [1, 2, 3, 4, 5, 6];

        $activity->getGuid()
            ->willReturn(null);

        $activity->set('guid', Argument::any())
            ->shouldBeCalled();

        $activity->getUrn()
            ->willReturn('urn:activity:123');

        $activity->getType()
            ->willReturn('activity');

        $activity->getSubtype()
            ->willReturn(null);

        $owner->getNsfw()
            ->shouldBeCalled()
            ->willReturn($nsfw);

        $owner->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([]);

        $owner->isMature()
            ->shouldBeCalled()
            ->willReturn(false);

        $owner->get('language')
            ->shouldBeCalled()
            ->willReturn(null);
        
        $activity->get('language')
            ->shouldBeCalled()
            ->willReturn(null);
        
        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->entitiesBuilderMock->single(123)->willReturn($owner);

        $activity->getContainerGuid()
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->setNsfw($nsfw)->shouldBeCalled();

        //
        
        $this->entitiesRepositoryMock->create($activity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->dispatcher->trigger('create', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entities-ops', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entity:save', Argument::any(), Argument::any(), true)
            ->willReturn(true);

        //

        $this->setEntity($activity);

        $this->save()->shouldReturn(true);
    }

    public function it_should_save_an_entity_using_its_save_method_with_NsfwLock_from_owner(Activity $activity, User $owner)
    {
        $nsfw = [1, 2, 3, 4, 5, 6];

        $activity->getGuid()
            ->willReturn(null);

        $activity->set('guid', Argument::any())
            ->shouldBeCalled();

        $activity->getUrn()
            ->willReturn('urn:activity:123');

        $activity->getType()
            ->willReturn('activity');

        $activity->getSubtype()
            ->willReturn(null);

        $owner->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $owner->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn($nsfw);

        $owner->isMature()
            ->shouldBeCalled()
            ->willReturn(false);

        $owner->get('language')
            ->shouldBeCalled()
            ->willReturn(null);
        
        $activity->get('language')
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $this->entitiesBuilderMock->single(123)->willReturn($owner);

        $activity->getContainerGuid()
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->setNsfw($nsfw)->shouldBeCalled();

        //
        
        $this->entitiesRepositoryMock->create($activity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->dispatcher->trigger('create', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entities-ops', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entity:save', Argument::any(), Argument::any(), true)
            ->willReturn(true);

        //

        $this->setEntity($activity);

        $this->save()->shouldReturn(true);
    }

    public function it_should_save_an_entity_using_its_save_method_with_NSFW_from_container(Activity $activity, Group $container)
    {
        $nsfw = [1, 2, 3, 4, 5, 6];
        
        $activity->getGuid()
            ->willReturn(null);

        $activity->set('guid', Argument::any())
            ->shouldBeCalled();

        $activity->getUrn()
            ->willReturn('urn:activity:123');

        $activity->getType()
            ->willReturn('activity');

        $activity->getSubtype()
            ->willReturn(null);
    
        $container->getNsfw()
            ->shouldBeCalled()
            ->willReturn($nsfw);

        $container->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->get('language')
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getContainerGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $this->entitiesBuilderMock->single(456)->willReturn($container);

        $activity->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->setNsfw($nsfw)->shouldBeCalled();

        //
        
        $this->entitiesRepositoryMock->create($activity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->dispatcher->trigger('create', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entities-ops', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entity:save', Argument::any(), Argument::any(), true)
            ->willReturn(true);

        //

        $this->setEntity($activity);

        $this->save()->shouldReturn(true);
    }

    public function it_should_save_an_entity_using_its_save_method_with_NSFW_from_group(Activity $activity, Group $container)
    {
        $nsfw = [1, 2, 3, 4, 5, 6];

        $activity->getGuid()
            ->willReturn(null);

        $activity->set('guid', Argument::any())
            ->shouldBeCalled();

        $activity->getUrn()
            ->willReturn('urn:activity:123');

        $activity->getType()
            ->willReturn('activity');

        $activity->getSubtype()
            ->willReturn(null);

        $container->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $container->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn($nsfw);

        $activity->get('language')
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getContainerGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $this->entitiesBuilderMock->single(456)->willReturn($container);

        $activity->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->setNsfw($nsfw)->shouldBeCalled();

        //
        
        $this->entitiesRepositoryMock->create($activity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->dispatcher->trigger('create', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entities-ops', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entity:save', Argument::any(), Argument::any(), true)
            ->willReturn(true);

        //

        $this->setEntity($activity);

        $this->save()->shouldReturn(true);
    }


    public function it_should_save_an_entity_using_its_save_method_with_merged_NSFW_from_container(Activity $activity, Group $container)
    {
        $nsfw = [1, 2, 3];
        $nsfwLock = [4, 5, 6];

        $activity->getGuid()
            ->willReturn(null);

        $activity->set('guid', Argument::any())
            ->shouldBeCalled();

        $activity->getUrn()
            ->willReturn('urn:activity:123');

        $activity->getType()
            ->willReturn('activity');

        $activity->getSubtype()
            ->willReturn(null);

        $container->getNsfw()
            ->shouldBeCalled()
            ->willReturn($nsfw);

        $container->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn($nsfwLock);
        
        $activity->get('language')
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getContainerGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $this->entitiesBuilderMock->single(456)->willReturn($container);

        $activity->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->getNsfwLock()
            ->shouldBeCalled()
            ->willReturn([]);

        $activity->setNsfw(array_merge($nsfw, $nsfwLock))->shouldBeCalled();

        $this->setEntity($activity);

        $this->entitiesRepositoryMock->create($activity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->dispatcher->trigger('create', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entities-ops', Argument::any(), Argument::any())
            ->willReturn(true);

        $this->dispatcher->trigger('entity:save', Argument::any(), Argument::any(), true)
            ->willReturn(true);

        $this->save()->shouldReturn(true);
    }

    public function it_should_set_entity_language_to_owner(Activity $activity, User $owner)
    {
        $owner->get('language')
            ->shouldBeCalled()
            ->willReturn('en');

        $activity->get('language')
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $activity->set('language', 'en')
            ->shouldBeCalled();

        $this->entitiesBuilderMock->single(123)
            ->willReturn($owner);
        
        $this->setEntity($activity);
        $this->applyLanguage();
    }

    public function it_should_not_replace_existing_language(Activity $activity, User $owner)
    {
        $activity->get('language')
            ->shouldBeCalled()
            ->willReturn('en');
        
        $owner->get('language')
            ->shouldBeCalledTimes(0)
            ->willReturn('en');

        $activity->set('language', 'en')
            ->shouldBeCalledTimes(0);
        
        $this->setEntity($activity);
        $this->applyLanguage();
    }
}
