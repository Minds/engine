<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\MobileConfigs\Deployments\Builds;

use GuzzleHttp\Client;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\MobileConfigs\Deployments\Builds\MobilePreviewHandler;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\Response;

class MobilePreviewHandlerSpec extends ObjectBehavior
{
    private Collaborator $httpClientMock;
    private Collaborator $configMock;
    private Collaborator $multiTenantBootServiceMock;

    public function let(
        Client                 $httpClient,
        Config                 $config,
        MultiTenantBootService $multiTenantBootService
    ): void {
        $this->httpClientMock = $httpClient;
        $this->configMock = $config;
        $this->multiTenantBootServiceMock = $multiTenantBootService;

        $this->beConstructedWith(
            $this->httpClientMock,
            $this->configMock,
            $this->multiTenantBootServiceMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(MobilePreviewHandler::class);
    }

    /**
     * @return void
     */
    public function it_should_trigger_pipeline(): void
    {
        $this->configMock->get('gitlab')->willReturn([
            'mobile' => [
                'pipeline' => [
                    'trigger_token' => 'token',
                ]
            ]
        ]);

        $this->configMock->get('tenant_id')->willReturn(1);
        $this->configMock->get('site_url')->willReturn('test');

        $this->multiTenantBootServiceMock->resetRootConfigs()->shouldBeCalledOnce();
        $this->multiTenantBootServiceMock->bootFromTenantId(1)->shouldBeCalledOnce();

        $this->httpClientMock->post(
            Argument::that(fn ($uri) => $uri === 'https://gitlab.com/api/v4/projects/10171280/trigger/pipeline'),
            Argument::that(
                fn ($options) => $options['form_params']['token'] === 'token' &&
                    $options['form_params']['ref'] === 'ci-preview-backend' &&
                    $options['form_params']['variables[BUILD_MODE]'] === 'PREVIEW' &&
                    $options['form_params']['variables[TENANT_ID]'] === 1 &&
                    $options['form_params']['variables[WEBHOOK_URL]'] === 'test/api/v3/multi-tenant/mobile-configs/update-preview' &&
                    $options['form_params']['variables[GRAPHQL_URL]'] === 'test/api/graphql' &&
                    $options['form_params']['variables[AUDIENCE]'] === 'test'
            )
        )
            ->shouldBeCalledOnce()
            ->willReturn(new Response("OK", 201));

        $this->triggerPipeline();
    }
}
