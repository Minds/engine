<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\MobileConfigs\Helpers;

use DateTimeImmutable;
use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\MobileConfigs\Helpers\GitlabPipelineJwtTokenValidator;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class GitlabPipelineJwtTokenValidatorSpec extends ObjectBehavior
{
    private Collaborator $jwtMock;
    private Collaborator $configMock;
    private Collaborator $multiTenantBootServiceMock;

    public function let(
        Jwt                    $jwt,
        Config                 $config,
        MultiTenantBootService $multiTenantBootService
    ): void {
        $this->jwtMock = $jwt;
        $this->configMock = $config;
        $this->multiTenantBootServiceMock = $multiTenantBootService;

        $this->beConstructedWith(
            $this->jwtMock,
            $this->configMock,
            $this->multiTenantBootServiceMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(GitlabPipelineJwtTokenValidator::class);
    }

    // checkToken

    public function it_is_valid_token(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'jwt_token' => [
                        'secret_key' => 'token',
                    ]
                ]
            ]
        ]);

        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);
        $this->configMock->get('site_url')->shouldBeCalledOnce()->willReturn('test');

        $this->jwtMock->setKey('token')
            ->shouldBeCalledOnce()
            ->willReturn($this->jwtMock);

        $this->jwtMock->decode('token')
            ->shouldBeCalledOnce()
            ->willReturn([
                'iss' => 'https://gitlab.com/api/v4/projects/10171280/trigger/pipeline',
                'aud' => ['test'],
                'exp' => (new DateTimeImmutable())->modify('+1 day'),
            ]);

        $this->multiTenantBootServiceMock->resetRootConfigs()->shouldBeCalledOnce();
        $this->multiTenantBootServiceMock->bootFromTenantId(1)->shouldBeCalledOnce();

        $this->checkToken('token')->shouldReturn(true);
    }

    public function it_is_invalid_token_with_wrong_issuer(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'jwt_token' => [
                        'secret_key' => 'token',
                    ]
                ]
            ]
        ]);

        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);

        $this->jwtMock->setKey('token')
            ->shouldBeCalledOnce()
            ->willReturn($this->jwtMock);

        $this->jwtMock->decode('token')
            ->shouldBeCalledOnce()
            ->willReturn([
                'iss' => 'test',
                'aud' => ['test'],
                'exp' => (new DateTimeImmutable())->modify('+1 day'),
            ]);

        $this->multiTenantBootServiceMock->resetRootConfigs()->shouldBeCalledOnce();
        $this->multiTenantBootServiceMock->bootFromTenantId(1)->shouldBeCalledOnce();

        $this->checkToken('token')->shouldReturn(false);
    }

    public function it_is_invalid_token_with_wrong_audience(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'jwt_token' => [
                        'secret_key' => 'token',
                    ]
                ]
            ]
        ]);

        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);
        $this->configMock->get('site_url')->shouldBeCalledOnce()->willReturn('test');

        $this->jwtMock->setKey('token')
            ->shouldBeCalledOnce()
            ->willReturn($this->jwtMock);

        $this->jwtMock->decode('token')
            ->shouldBeCalledOnce()
            ->willReturn([
                'iss' => 'https://gitlab.com/api/v4/projects/10171280/trigger/pipeline',
                'aud' => ['wrong-audience'],
                'exp' => (new DateTimeImmutable())->modify('+1 day'),
            ]);

        $this->multiTenantBootServiceMock->resetRootConfigs()->shouldBeCalledOnce();
        $this->multiTenantBootServiceMock->bootFromTenantId(1)->shouldBeCalledOnce();

        $this->checkToken('token')->shouldReturn(false);
    }

    public function it_is_invalid_token_with_expired_validity(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'jwt_token' => [
                        'secret_key' => 'token',
                    ]
                ]
            ]
        ]);

        $this->configMock->get('tenant_id')->shouldBeCalledOnce()->willReturn(1);
        $this->configMock->get('site_url')->shouldBeCalledOnce()->willReturn('test');

        $this->jwtMock->setKey('token')
            ->shouldBeCalledOnce()
            ->willReturn($this->jwtMock);

        $this->jwtMock->decode('token')
            ->shouldBeCalledOnce()
            ->willReturn([
                'iss' => 'https://gitlab.com/api/v4/projects/10171280/trigger/pipeline',
                'aud' => ['test'],
                'exp' => (new DateTimeImmutable())->modify('-1 day'),
            ]);

        $this->multiTenantBootServiceMock->resetRootConfigs()->shouldBeCalledOnce();
        $this->multiTenantBootServiceMock->bootFromTenantId(1)->shouldBeCalledOnce();

        $this->checkToken('token')->shouldReturn(false);
    }

    // checkTokenForNonTenant

    public function it_is_valid_token_for_non_tenant(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'jwt_token' => [
                        'secret_key' => 'token',
                    ]
                ]
            ]
        ]);

        $this->configMock->get('site_url')->shouldBeCalledOnce()->willReturn('test');

        $this->jwtMock->setKey('token')
            ->shouldBeCalledOnce()
            ->willReturn($this->jwtMock);

        $this->jwtMock->decode('token')
            ->shouldBeCalledOnce()
            ->willReturn([
                'iss' => 'https://gitlab.com/api/v4/projects/10171280/trigger/pipeline',
                'aud' => ['test'],
                'exp' => (new DateTimeImmutable())->modify('+1 day'),
            ]);

        $this->checkTokenForNonTenant('token')->shouldReturn(true);
    }

    public function it_is_invalid_token_for_non_tenant_with_wrong_issuer(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'jwt_token' => [
                        'secret_key' => 'token',
                    ]
                ]
            ]
        ]);

        $this->jwtMock->setKey('token')
            ->shouldBeCalledOnce()
            ->willReturn($this->jwtMock);

        $this->jwtMock->decode('token')
            ->shouldBeCalledOnce()
            ->willReturn([
                'iss' => 'wrong-issuer',
                'aud' => ['test'],
                'exp' => (new DateTimeImmutable())->modify('+1 day'),
            ]);

        $this->checkTokenForNonTenant('token')->shouldReturn(false);
    }

    public function it_is_invalid_token_for_non_tenant_with_wrong_audience(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'jwt_token' => [
                        'secret_key' => 'token',
                    ]
                ]
            ]
        ]);

        $this->configMock->get('site_url')->shouldBeCalledOnce()->willReturn('test');

        $this->jwtMock->setKey('token')
            ->shouldBeCalledOnce()
            ->willReturn($this->jwtMock);

        $this->jwtMock->decode('token')
            ->shouldBeCalledOnce()
            ->willReturn([
                'iss' => 'https://gitlab.com/api/v4/projects/10171280/trigger/pipeline',
                'aud' => ['wrong-audience'],
                'exp' => (new DateTimeImmutable())->modify('+1 day'),
            ]);

        $this->checkTokenForNonTenant('token')->shouldReturn(false);
    }

    public function it_is_invalid_token_for_non_tenant_with_expired_validity(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'jwt_token' => [
                        'secret_key' => 'token',
                    ]
                ]
            ]
        ]);

        $this->configMock->get('site_url')->shouldBeCalledOnce()->willReturn('test');

        $this->jwtMock->setKey('token')
            ->shouldBeCalledOnce()
            ->willReturn($this->jwtMock);

        $this->jwtMock->decode('token')
            ->shouldBeCalledOnce()
            ->willReturn([
                'iss' => 'https://gitlab.com/api/v4/projects/10171280/trigger/pipeline',
                'aud' => ['test'],
                'exp' => (new DateTimeImmutable())->modify('-1 day'),
            ]);

        $this->checkTokenForNonTenant('token')->shouldReturn(false);
    }

    public function it_returns_false_for_non_tenant_when_jwt_decode_throws_exception(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'jwt_token' => [
                        'secret_key' => 'token',
                    ]
                ]
            ]
        ]);

        $this->jwtMock->setKey('token')
            ->shouldBeCalledOnce()
            ->willReturn($this->jwtMock);

        $this->jwtMock->decode('token')
            ->shouldBeCalledOnce()
            ->willThrow(new \Exception('Invalid token'));

        $this->checkTokenForNonTenant('token')->shouldReturn(false);
    }
}
