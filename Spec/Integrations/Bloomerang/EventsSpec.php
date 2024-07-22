<?php

namespace Spec\Minds\Integrations\Bloomerang;

use Minds\Core\Config\Config;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Entities\User;
use Minds\Integrations\Bloomerang\BloomerangConstituentService;
use Minds\Integrations\Bloomerang\Events;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class EventsSpec extends ObjectBehavior
{
    private Collaborator $eventsDispatcherMock;
    private Collaborator $configMock;
    private Collaborator $bloomerangConstituentServiceMock;

    public function let(EventsDispatcher $eventsDispatcherMock, Config $configMock, BloomerangConstituentService $bloomerangConstituentServiceMock)
    {
        $this->beConstructedWith($eventsDispatcherMock, $configMock, $bloomerangConstituentServiceMock);
        $this->eventsDispatcherMock = $eventsDispatcherMock;
        $this->configMock = $configMock;
        $this->bloomerangConstituentServiceMock = $bloomerangConstituentServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Events::class);
    }

    public function it_should_register_save_and_validate_events()
    {
        $this->eventsDispatcherMock->register('entity:save', 'user', [$this, 'onUserSaveFn'])
            ->shouldBeCalled();
        $this->eventsDispatcherMock->register('site-membership:revalidate', 'all', [$this, 'onSiteMembershipRevalidateFn'])
            ->shouldBeCalled();

        $this->register();
    }

    public function it_should_sync_a_user_on_save()
    {
        $user = new User();

        $tenant = new Tenant(
            id: 1,
            config: new MultiTenantConfig(
                bloomerangApiKey: 'fake-key'
            )
        );

        $this->configMock->get('tenant')
            ->willReturn($tenant);

        $this->bloomerangConstituentServiceMock->syncUser($user)
            ->shouldBeCalled();

        $this->onUserSaveFn(new Event([
           'parameters' => [
                'entity' => $user
           ]
        ]));
    }

    public function it_should_NOT_sync_a_user_on_save_if_not_setup_bloomerang()
    {
        $user = new User();

        $tenant = new Tenant(
            id: 1,
            config: new MultiTenantConfig(
            )
        );

        $this->configMock->get('tenant')
            ->willReturn($tenant);

        $this->bloomerangConstituentServiceMock->syncUser(Argument::any())
            ->shouldNotBeCalled();

        $this->onUserSaveFn(new Event([
           'parameters' => [
                'entity' => $user
           ]
        ]));
    }

    public function it_should_sync_a_user_on_revalidate()
    {
        $user = new User();

        $tenant = new Tenant(
            id: 1,
            config: new MultiTenantConfig(
                bloomerangApiKey: 'fake-key'
            )
        );

        $this->configMock->get('tenant')
            ->willReturn($tenant);

        $this->bloomerangConstituentServiceMock->syncUser($user)
            ->shouldBeCalled();

        $this->onSiteMembershipRevalidateFn(new Event([
           'parameters' => [
                'user' => $user
           ]
        ]));
    }

    public function it_should_NOT_sync_a_user_on_revalidate_if_not_setup_bloomerang()
    {
        $user = new User();

        $tenant = new Tenant(
            id: 1,
            config: new MultiTenantConfig(
            )
        );

        $this->configMock->get('tenant')
            ->willReturn($tenant);

        $this->bloomerangConstituentServiceMock->syncUser(Argument::any())
            ->shouldNotBeCalled();

        $this->onSiteMembershipRevalidateFn(new Event([
           'parameters' => [
                'user' => $user
           ]
        ]));
    }
}
