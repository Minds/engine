<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\TenantUserWelcome;

use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\Manager;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantUserWelcome\TenantUserWelcomeEmailer;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Guid;
use Minds\Core\MultiTenant\Enums\FeaturedEntityTypeEnum;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\MultiTenant\Types\FeaturedEntityConnection;
use Minds\Core\MultiTenant\Types\FeaturedEntityEdge;
use Minds\Core\MultiTenant\Types\FeaturedGroup;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class TenantUserWelcomeEmailerSpec extends ObjectBehavior
{
    protected Collaborator $templateMock;
    protected Collaborator $mailerMock;
    protected Collaborator $configMock;
    protected Collaborator $tenantTemplateVariableInjectorMock;
    protected Collaborator $siteMembershipReaderServiceMock;
    protected Collaborator $siteMembershipSubscriptionsService;
    protected Collaborator $featuredEntityServiceMock;
    protected Collaborator $managerMock;

    public function let(
        Template $template,
        Mailer $mailer,
        Config $config,
        TenantTemplateVariableInjector $tenantTemplateVariableInjector,
        SiteMembershipReaderService $siteMembershipReaderService,
        SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService,
        FeaturedEntityService $featuredEntityService,
        Manager $manager,
    ) {
        $this->templateMock = $template;
        $this->mailerMock = $mailer;
        $this->configMock = $config;
        $this->tenantTemplateVariableInjectorMock = $tenantTemplateVariableInjector;
        $this->siteMembershipReaderServiceMock = $siteMembershipReaderService;
        $this->siteMembershipSubscriptionsService = $siteMembershipSubscriptionsService;
        $this->featuredEntityServiceMock = $featuredEntityService;
        $this->managerMock = $manager;

        $this->beConstructedWith(
            $this->templateMock,
            $this->mailerMock,
            $this->configMock,
            $this->tenantTemplateVariableInjectorMock,
            $this->siteMembershipReaderServiceMock,
            $this->siteMembershipSubscriptionsService,
            $this->featuredEntityServiceMock,
            $this->managerMock
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TenantUserWelcomeEmailer::class);
    }

    public function it_should_build_with_no_memberships_or_featured_groups(
        User $user,
        FeaturedEntityConnection $featuredEntityConnection
    ): void {
        $siteName = 'Testnet';
        $siteUrl = 'https://example.minds.com/';
        $username = 'testuser';
      
        $email = 'noreply@minds.com';
        $tenantId = 123;
        $userGuid = Guid::build();
        $siteMemberships = [];
        $featuredGroups = [];

        $featuredEntityConnection->getEdges()
          ->shouldBeCalled()
          ->willReturn($featuredGroups);

        $user->username = $username;

        $user->get('username')
          ->shouldBeCalled($username);

        $user->get('name')
          ->shouldBeCalled($username);

        $user->getEmail()
          ->shouldBeCalled()
          ->willReturn($email);

        $user->getGUID()
          ->shouldBeCalled()
          ->willReturn($userGuid);

        $this->setUser($user);

        $this->configMock->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->templateMock->clear()
          ->shouldBeCalled();

        $this->templateMock->setTemplate('default.v2.tpl')
          ->shouldBeCalled();

        $this->templateMock->setBody('./template.tpl')
          ->shouldBeCalled();

        $this->templateMock->set('headerText', "Welcome!")
          ->shouldBeCalled();

        $this->templateMock->set('bodyText', "Thanks for joining $siteName. Here's what you can do next to get the most out of the community.")
          ->shouldBeCalled();

        $this->templateMock->set('preheader', "Thanks for joining $siteName")
          ->shouldBeCalled();

        $this->templateMock->set('user', $user)
          ->shouldBeCalled();

        $this->templateMock->set('username', Argument::any())
          ->shouldBeCalled();

        $this->templateMock->set('email', $email)
          ->shouldBeCalled();

        $this->templateMock->set('guid', $userGuid)
          ->shouldBeCalled();

        $this->templateMock->set('campaign', 'with')
          ->shouldBeCalled();

        $this->templateMock->set('topic', 'welcome')
          ->shouldBeCalled();

        $this->templateMock->set('tracking', Argument::type('string'))
          ->shouldBeCalled();

        $this->configMock->get('tenant_id')
          ->shouldBeCalled()
          ->willReturn($tenantId);

        $this->tenantTemplateVariableInjectorMock->inject($this->templateMock)
          ->shouldBeCalled()
          ->willReturn($this->templateMock);

        $this->templateMock->set('actionButton', Argument::any())
          ->shouldBeCalled();
     
        $this->siteMembershipSubscriptionsService->hasActiveSiteMembershipSubscription(
            user: $user
        )
          ->shouldBeCalled()
          ->willReturn(false);

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
          ->shouldBeCalled()
          ->willReturn($siteMemberships);

        $this->templateMock->set('site_membership_containers', $siteMemberships)
          ->shouldBeCalled();

        $this->featuredEntityServiceMock->getFeaturedEntities(
            type: FeaturedEntityTypeEnum::GROUP,
            loadAfter: 0,
            limit: 3
        )
          ->shouldBeCalled()
          ->willReturn($featuredEntityConnection);

        $this->templateMock->set('featured_group_containers', $featuredGroups)
          ->shouldBeCalled();

        $this->configMock->get('site_url')
          ->shouldBeCalled()
          ->willReturn($siteUrl);

        $this->build();
    }

    public function it_should_build_with_memberships_and_featured_groups(
        User $user,
        FeaturedEntityConnection $featuredEntityConnection,
        FeaturedEntityEdge $featuredEntityEdge
    ): void {
        $siteName = 'Testnet';
        $siteUrl = 'https://example.minds.com/';
        $username = 'testuser';
        
        $email = 'noreply@minds.com';
        $tenantId = 123;
        $userGuid = Guid::build();
        $siteMemberships = [new SiteMembership(
            membershipGuid: Guid::build(),
            membershipName: 'Test Membership',
            membershipPriceInCents: 999,
            membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY,
            membershipPricingModel: SiteMembershipPricingModelEnum::RECURRING,
            membershipDescription: 'Test Membership Description',
        )];
        
        $featuredEntityEdge->getNode()
          ->shouldBeCalled()
          ->willReturn(new FeaturedGroup(
              tenantId: 123,
              entityGuid: Guid::build(),
              autoSubscribe: false,
              recommended: false,
              autoPostSubscription: false,
              name: 'Test Group',
              briefDescription: 'Test Group Description',
              membersCount: 3
          ));
        
        $featuredEntityConnection->getEdges()
          ->shouldBeCalled()
          ->willReturn([$featuredEntityEdge]);
  
        $user->username = $username;
  
        $user->get('username')
          ->shouldBeCalled($username);
  
        $user->get('name')
          ->shouldBeCalled($username);
  
        $user->getEmail()
          ->shouldBeCalled()
          ->willReturn($email);
  
        $user->getGUID()
          ->shouldBeCalled()
          ->willReturn($userGuid);
  
        $this->setUser($user);
  
        $this->configMock->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);
  
        $this->templateMock->clear()
          ->shouldBeCalled();

        $this->templateMock->setTemplate('default.v2.tpl')
          ->shouldBeCalled();
  
        $this->templateMock->setBody('./template.tpl')
          ->shouldBeCalled();
  
        $this->templateMock->set('headerText', "Welcome!")
          ->shouldBeCalled();
  
        $this->templateMock->set('bodyText', "Thanks for joining $siteName. Here's what you can do next to get the most out of the community.")
          ->shouldBeCalled();
  
        $this->templateMock->set('preheader', "Thanks for joining $siteName")
          ->shouldBeCalled();
  
        $this->templateMock->set('user', $user)
          ->shouldBeCalled();
  
        $this->templateMock->set('username', Argument::any())
          ->shouldBeCalled();
  
        $this->templateMock->set('email', $email)
          ->shouldBeCalled();
  
        $this->templateMock->set('guid', $userGuid)
          ->shouldBeCalled();
  
        $this->templateMock->set('campaign', 'with')
          ->shouldBeCalled();
  
        $this->templateMock->set('topic', 'welcome')
          ->shouldBeCalled();
  
        $this->templateMock->set('tracking', Argument::type('string'))
          ->shouldBeCalled();
  
        $this->configMock->get('tenant_id')
          ->shouldBeCalled()
          ->willReturn($tenantId);
  
        $this->tenantTemplateVariableInjectorMock->inject($this->templateMock)
          ->shouldBeCalled()
          ->willReturn($this->templateMock);
  
        $this->templateMock->set('actionButton', Argument::any())
          ->shouldBeCalled();

        $this->siteMembershipSubscriptionsService->hasActiveSiteMembershipSubscription(
            user: $user
        )
          ->shouldBeCalled()
          ->willReturn(false);

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
          ->shouldBeCalled()
          ->willReturn($siteMemberships);
  
        $this->templateMock->set('site_membership_containers', Argument::that(function ($siteMembershipContainers) {
            return $siteMembershipContainers[0]['name'] === 'Test Membership'
              && $siteMembershipContainers[0]['description'] === 'Test Membership Description'
              && $siteMembershipContainers[0]['pricingLabel'] === '$9.99 / month';
        }))
          ->shouldBeCalled();
  
        $this->featuredEntityServiceMock->getFeaturedEntities(
            type: FeaturedEntityTypeEnum::GROUP,
            loadAfter: 0,
            limit: 3
        )
          ->shouldBeCalled()
          ->willReturn($featuredEntityConnection);
  
        $this->templateMock->set('featured_group_containers', Argument::that(function ($featuredGroups) {
            return $featuredGroups[0]['name'] === 'Test Group'
              && $featuredGroups[0]['description'] === 'Test Group Description'
              && str_starts_with($featuredGroups[0]['avatar_url'], 'https://example.minds.com/fs/v1/avatars')
              && str_starts_with($featuredGroups[0]['join_url'], 'https://example.minds.com/group/');
        }))
          ->shouldBeCalled();
  
        $this->configMock->get('site_url')
          ->shouldBeCalled()
          ->willReturn($siteUrl);
  
        $this->build();
    }

    public function it_should_build_with_no_memberships_when_user_has_an_active_membership(
        User $user,
        FeaturedEntityConnection $featuredEntityConnection,
        FeaturedEntityEdge $featuredEntityEdge
    ): void {
        $siteName = 'Testnet';
        $siteUrl = 'https://example.minds.com/';
        $username = 'testuser';
      
        $email = 'noreply@minds.com';
        $tenantId = 123;
        $userGuid = Guid::build();
        $siteMemberships = [new SiteMembership(
            membershipGuid: Guid::build(),
            membershipName: 'Test Membership',
            membershipPriceInCents: 999,
            membershipBillingPeriod: SiteMembershipBillingPeriodEnum::MONTHLY,
            membershipPricingModel: SiteMembershipPricingModelEnum::RECURRING,
            membershipDescription: 'Test Membership Description',
        )];
      
        $featuredEntityEdge->getNode()
          ->shouldBeCalled()
          ->willReturn(new FeaturedGroup(
              tenantId: 123,
              entityGuid: Guid::build(),
              autoSubscribe: false,
              recommended: false,
              autoPostSubscription: false,
              name: 'Test Group',
              briefDescription: 'Test Group Description',
              membersCount: 3
          ));
      
        $featuredEntityConnection->getEdges()
          ->shouldBeCalled()
          ->willReturn([$featuredEntityEdge]);

        $user->username = $username;

        $user->get('username')
          ->shouldBeCalled($username);

        $user->get('name')
          ->shouldBeCalled($username);

        $user->getEmail()
          ->shouldBeCalled()
          ->willReturn($email);

        $user->getGUID()
          ->shouldBeCalled()
          ->willReturn($userGuid);

        $this->setUser($user);

        $this->configMock->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->templateMock->clear()
          ->shouldBeCalled();

        $this->templateMock->setTemplate('default.v2.tpl')
          ->shouldBeCalled();

        $this->templateMock->setBody('./template.tpl')
          ->shouldBeCalled();

        $this->templateMock->set('headerText', "Welcome!")
          ->shouldBeCalled();

        $this->templateMock->set('bodyText', "Thanks for joining $siteName. Here's what you can do next to get the most out of the community.")
          ->shouldBeCalled();

        $this->templateMock->set('preheader', "Thanks for joining $siteName")
          ->shouldBeCalled();

        $this->templateMock->set('user', $user)
          ->shouldBeCalled();

        $this->templateMock->set('username', Argument::any())
          ->shouldBeCalled();

        $this->templateMock->set('email', $email)
          ->shouldBeCalled();

        $this->templateMock->set('guid', $userGuid)
          ->shouldBeCalled();

        $this->templateMock->set('campaign', 'with')
          ->shouldBeCalled();

        $this->templateMock->set('topic', 'welcome')
          ->shouldBeCalled();

        $this->templateMock->set('tracking', Argument::type('string'))
          ->shouldBeCalled();

        $this->configMock->get('tenant_id')
          ->shouldBeCalled()
          ->willReturn($tenantId);

        $this->tenantTemplateVariableInjectorMock->inject($this->templateMock)
          ->shouldBeCalled()
          ->willReturn($this->templateMock);

        $this->templateMock->set('actionButton', Argument::any())
          ->shouldBeCalled();

        $this->siteMembershipSubscriptionsService->hasActiveSiteMembershipSubscription(
            user: $user
        )
          ->shouldBeCalled()
          ->willReturn(true);

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
          ->shouldNotBeCalled();

        $this->templateMock->set('site_membership_containers', [])
          ->shouldBeCalled();

        $this->featuredEntityServiceMock->getFeaturedEntities(
            type: FeaturedEntityTypeEnum::GROUP,
            loadAfter: 0,
            limit: 3
        )
          ->shouldBeCalled()
          ->willReturn($featuredEntityConnection);

        $this->templateMock->set('featured_group_containers', Argument::that(function ($featuredGroups) {
            return $featuredGroups[0]['name'] === 'Test Group'
              && $featuredGroups[0]['description'] === 'Test Group Description'
              && str_starts_with($featuredGroups[0]['avatar_url'], 'https://example.minds.com/fs/v1/avatars')
              && str_starts_with($featuredGroups[0]['join_url'], 'https://example.minds.com/group/');
        }))
          ->shouldBeCalled();

        $this->configMock->get('site_url')
          ->shouldBeCalled()
          ->willReturn($siteUrl);

        $this->build();
    }

    // send

    public function it_should_send_when_welcome_email_is_enabled(
        User $user,
        FeaturedEntityConnection $featuredEntityConnection
    ): void {
        $siteName = 'Testnet';
        $siteUrl = 'https://example.minds.com/';
        $username = 'testuser';
      
        $email = 'noreply@minds.com';
        $tenantId = 123;
        $userGuid = Guid::build();
        $siteMemberships = [];
        $featuredGroups = [];

        $this->configMock->get('tenant')
          ->shouldBeCalled()
          ->willReturn((object) [
            'config' => (object) [
              'welcomeEmailEnabled' => true
            ]
          ]);

        $this->managerMock->isSubscribed(Argument::any())
          ->shouldBeCalled()
          ->willReturn(true);

        $featuredEntityConnection->getEdges()
          ->shouldBeCalled()
          ->willReturn($featuredGroups);

        $user->username = $username;

        $user->get('username')
          ->shouldBeCalled($username);

        $user->get('name')
          ->shouldBeCalled($username);

        $user->get('enabled')
          ->shouldBeCalled()
          ->willReturn(true);
        
        $user->get('banned')
          ->shouldBeCalled()
          ->willReturn(false);

        $user->get('guid')
          ->shouldBeCalled()
          ->willReturn($userGuid);

        $user->getEmail()
          ->shouldBeCalled()
          ->willReturn($email);

        $user->getGUID()
          ->shouldBeCalled()
          ->willReturn($userGuid);

        $this->setUser($user);

        $this->configMock->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->templateMock->clear()
            ->shouldBeCalled();

        $this->templateMock->setTemplate('default.v2.tpl')
          ->shouldBeCalled();

        $this->templateMock->setBody('./template.tpl')
          ->shouldBeCalled();

        $this->templateMock->set('headerText', "Welcome!")
          ->shouldBeCalled();

        $this->templateMock->set('bodyText', "Thanks for joining $siteName. Here's what you can do next to get the most out of the community.")
          ->shouldBeCalled();

        $this->templateMock->set('preheader', "Thanks for joining $siteName")
          ->shouldBeCalled();

        $this->templateMock->set('user', $user)
          ->shouldBeCalled();

        $this->templateMock->set('username', Argument::any())
          ->shouldBeCalled();

        $this->templateMock->set('email', $email)
          ->shouldBeCalled();

        $this->templateMock->set('guid', $userGuid)
          ->shouldBeCalled();

        $this->templateMock->set('campaign', 'with')
          ->shouldBeCalled();

        $this->templateMock->set('topic', 'welcome')
          ->shouldBeCalled();

        $this->templateMock->set('tracking', Argument::type('string'))
          ->shouldBeCalled();

        $this->configMock->get('tenant_id')
          ->shouldBeCalled()
          ->willReturn($tenantId);

        $this->tenantTemplateVariableInjectorMock->inject($this->templateMock)
          ->shouldBeCalled()
          ->willReturn($this->templateMock);

        $this->templateMock->set('actionButton', Argument::any())
          ->shouldBeCalled();

        $this->siteMembershipSubscriptionsService->hasActiveSiteMembershipSubscription(
            user: $user
        )
          ->shouldBeCalled()
          ->willReturn(false);

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
          ->shouldBeCalled()
          ->willReturn($siteMemberships);

        $this->templateMock->set('site_membership_containers', $siteMemberships)
          ->shouldBeCalled();

        $this->featuredEntityServiceMock->getFeaturedEntities(
            type: FeaturedEntityTypeEnum::GROUP,
            loadAfter: 0,
            limit: 3
        )
          ->shouldBeCalled()
          ->willReturn($featuredEntityConnection);

        $this->templateMock->set('featured_group_containers', $featuredGroups)
          ->shouldBeCalled();

        $this->configMock->get('site_url')
          ->shouldBeCalled()
          ->willReturn($siteUrl);

        $this->mailerMock->send(Argument::any())
          ->shouldBeCalled();
  
        $this->managerMock->saveCampaignLog(Argument::any())
          ->shouldBeCalled();

        $this->send();
    }

    public function it_should_NOT_send_when_welcome_email_is_disabled(
        User $user
    ): void {
        $username = 'testuser';
        $userGuid = Guid::build();

        $this->configMock->get('tenant')
          ->shouldBeCalled()
          ->willReturn((object) [
            'config' => (object) [
              'welcomeEmailEnabled' => false
            ]
          ]);

        $user->username = $username;

        $this->setUser($user);

        $this->templateMock->setTemplate('default.v2.tpl')
          ->shouldNotBeCalled();

        $this->templateMock->setBody('./template.tpl')
          ->shouldNotBeCalled();

        $this->mailerMock->send(Argument::any())
          ->shouldNotBeCalled();

        $this->managerMock->saveCampaignLog(Argument::any())
          ->shouldNotBeCalled();

        $this->send();
    }

    // queue

    public function it_should_queue_when_welcome_email_is_enabled(
        User $user,
        FeaturedEntityConnection $featuredEntityConnection
    ): void {
        $siteName = 'Testnet';
        $siteUrl = 'https://example.minds.com/';
        $username = 'testuser';
    
        $email = 'noreply@minds.com';
        $tenantId = 123;
        $userGuid = Guid::build();
        $siteMemberships = [];
        $featuredGroups = [];

        $this->configMock->get('tenant')
          ->shouldBeCalled()
          ->willReturn((object) [
            'config' => (object) [
              'welcomeEmailEnabled' => true
            ]
          ]);

        $this->managerMock->isSubscribed(Argument::any())
          ->shouldBeCalled()
          ->willReturn(true);

        $featuredEntityConnection->getEdges()
          ->shouldBeCalled()
          ->willReturn($featuredGroups);

        $user->username = $username;

        $user->get('username')
          ->shouldBeCalled($username);

        $user->get('name')
          ->shouldBeCalled($username);

        $user->get('enabled')
          ->shouldBeCalled()
          ->willReturn(true);
      
        $user->get('banned')
          ->shouldBeCalled()
          ->willReturn(false);

        $user->get('guid')
          ->shouldBeCalled()
          ->willReturn($userGuid);

        $user->getEmail()
          ->shouldBeCalled()
          ->willReturn($email);

        $user->getGUID()
          ->shouldBeCalled()
          ->willReturn($userGuid);

        $this->setUser($user);

        $this->configMock->get('site_name')
            ->shouldBeCalled()
            ->willReturn($siteName);

        $this->templateMock->clear()
          ->shouldBeCalled();

        $this->templateMock->setTemplate('default.v2.tpl')
          ->shouldBeCalled();

        $this->templateMock->setBody('./template.tpl')
          ->shouldBeCalled();

        $this->templateMock->set('headerText', "Welcome!")
          ->shouldBeCalled();

        $this->templateMock->set('bodyText', "Thanks for joining $siteName. Here's what you can do next to get the most out of the community.")
          ->shouldBeCalled();

        $this->templateMock->set('preheader', "Thanks for joining $siteName")
          ->shouldBeCalled();

        $this->templateMock->set('user', $user)
          ->shouldBeCalled();

        $this->templateMock->set('username', Argument::any())
          ->shouldBeCalled();

        $this->templateMock->set('email', $email)
          ->shouldBeCalled();

        $this->templateMock->set('guid', $userGuid)
          ->shouldBeCalled();

        $this->templateMock->set('campaign', 'with')
          ->shouldBeCalled();

        $this->templateMock->set('topic', 'welcome')
          ->shouldBeCalled();

        $this->templateMock->set('tracking', Argument::type('string'))
          ->shouldBeCalled();

        $this->configMock->get('tenant_id')
          ->shouldBeCalled()
          ->willReturn($tenantId);

        $this->tenantTemplateVariableInjectorMock->inject($this->templateMock)
          ->shouldBeCalled()
          ->willReturn($this->templateMock);

        $this->templateMock->set('actionButton', Argument::any())
          ->shouldBeCalled();

        $this->siteMembershipSubscriptionsService->hasActiveSiteMembershipSubscription(
            user: $user
        )
          ->shouldBeCalled()
          ->willReturn(false);

        $this->siteMembershipReaderServiceMock->getSiteMemberships()
          ->shouldBeCalled()
          ->willReturn($siteMemberships);

        $this->templateMock->set('site_membership_containers', $siteMemberships)
          ->shouldBeCalled();

        $this->featuredEntityServiceMock->getFeaturedEntities(
            type: FeaturedEntityTypeEnum::GROUP,
            loadAfter: 0,
            limit: 3
        )
          ->shouldBeCalled()
          ->willReturn($featuredEntityConnection);

        $this->templateMock->set('featured_group_containers', $featuredGroups)
          ->shouldBeCalled();

        $this->configMock->get('site_url')
          ->shouldBeCalled()
          ->willReturn($siteUrl);

        $this->managerMock->saveCampaignLog(Argument::any())
          ->shouldBeCalled();

        $this->mailerMock->queue(Argument::any())
          ->shouldBeCalled();

        $this->queue();
    }

    public function it_should_NOT_queue_when_welcome_email_is_disabled(
        User $user
    ): void {
        $username = 'testuser';
        $userGuid = Guid::build();

        $this->configMock->get('tenant')
          ->shouldBeCalled()
          ->willReturn((object) [
            'config' => (object) [
              'welcomeEmailEnabled' => false
            ]
          ]);

        $user->username = $username;

        $this->setUser($user);

        $this->templateMock->setTemplate('default.v2.tpl')
          ->shouldNotBeCalled();

        $this->templateMock->setBody('./template.tpl')
          ->shouldNotBeCalled();

        $this->mailerMock->queue(Argument::any())
          ->shouldNotBeCalled();

        $this->managerMock->saveCampaignLog(Argument::any())
          ->shouldNotBeCalled();

        $this->queue();
    }
}
