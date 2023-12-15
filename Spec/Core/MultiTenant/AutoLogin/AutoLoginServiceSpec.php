<?php

namespace Spec\Minds\Core\MultiTenant\AutoLogin;

use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\Cassandra;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\AutoLogin\AutoLoginService;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class AutoLoginServiceSpec extends ObjectBehavior
{
    private Collaborator $entitiesBuilderMock;
    private Collaborator $sessionsManagerMock;
    private Collaborator $tenantDataServiceMock;
    private Collaborator $tenantDomainServiceMock;
    private Collaborator $tmpStoreMock;
    private Collaborator $jwtMock;
    private Collaborator $configMock;
 
    public function let(
        EntitiesBuilder $entitiesBuilderMock,
        SessionsManager $sessionsManagerMock,
        MultiTenantDataService $tenantDataServiceMock,
        DomainService $tenantDomainServiceMock,
        Cassandra $tmpStoreMock,
        Jwt $jwtMock,
        Config $configMock,
    ) {
        $this->beConstructedWith($entitiesBuilderMock, $sessionsManagerMock, $tenantDataServiceMock, $tenantDomainServiceMock, $tmpStoreMock, $jwtMock, $configMock);
    
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->sessionsManagerMock = $sessionsManagerMock;
        $this->tenantDataServiceMock = $tenantDataServiceMock;
        $this->tenantDomainServiceMock = $tenantDomainServiceMock;
        $this->tmpStoreMock = $tmpStoreMock;
        $this->jwtMock = $jwtMock;
        $this->configMock = $configMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AutoLoginService::class);
    }

    public function it_should_build_a_login_url(User $userMock)
    {
        $tenant = new Tenant(
            id: 1,
            ownerGuid: 123,
            rootUserGuid: 456
        );
    
        $this->tenantDataServiceMock->getTenantFromId(1)
            ->willReturn($tenant);

        $this->tenantDomainServiceMock->buildDomain($tenant)
            ->willReturn('phpspec.local');

        $userMock->getGuid()
            ->willReturn(123);

        //

        $url = $this->buildLoginUrl(1, $userMock);
        $url->shouldContain('phpspec.local');
    }

    public function it_should_build_a_jwt_token(User $userMock)
    {
        $tenant = new Tenant(
            id: 1,
            ownerGuid: 123,
            rootUserGuid: 456
        );
    
        $this->tenantDataServiceMock->getTenantFromId(1)
            ->willReturn($tenant);

        $userMock->getGuid()
            ->willReturn(123);

        //

        $this->jwtMock->randomString()->willReturn('random');

        $this->tmpStoreMock->set(Argument::type('string'), Argument::any(), Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn(true);

        $encryptionKey = 'enc-key';

        $this->configMock->get('oauth')
            ->willReturn([
                'encryption_key' => $encryptionKey,
            ]);

        $this->jwtMock->setKey($encryptionKey)
            ->willReturn($this->jwtMock);
        
        $this->jwtMock->encode(Argument::type('array'), Argument::type('int'), Argument::type('int'), )
            ->willReturn('jwt-token');

        //

        $url = $this->buildJwtToken(1, $userMock);
        $url->shouldContain('jwt-token');
    }

    public function it_should_perform_login()
    {
        $this->configMock->get('tenant_id')->willReturn(1);

        $user = new User();

        $this->entitiesBuilderMock->single(456)
            ->willReturn($user);

        //

        $encryptionKey = 'enc-key';

        $this->configMock->get('oauth')
            ->willReturn([
                'encryption_key' => $encryptionKey,
            ]);

        $this->jwtMock->setKey($encryptionKey)
            ->willReturn($this->jwtMock);
        
        $this->jwtMock->decode('jwt-token-testing')
            ->willReturn([
                'user_guid' => 456,
                'tenant_id' => 1,
                'sso_token' => 'sso_token'
            ]);

        //

        $this->tmpStoreMock->get(Argument::type('string'))
            ->willReturn(true);

        $this->tmpStoreMock->delete(Argument::type('string'))
            ->willReturn(true);

        //

        $this->sessionsManagerMock->setUser($user)
            ->willReturn($this->sessionsManagerMock);

        $this->sessionsManagerMock->createSession()
            ->willReturn(true);
        
        $this->sessionsManagerMock->save()
            ->willReturn(true);

        //

        $this->performLogin('jwt-token-testing');
    }
}
