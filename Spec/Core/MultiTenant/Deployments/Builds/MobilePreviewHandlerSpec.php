<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Deployments\Builds;

use GuzzleHttp\Client;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Deployments\Builds\MobilePreviewHandler;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\Response;

class MobilePreviewHandlerSpec extends ObjectBehavior
{
    private Collaborator $httpClientMock;
    private Collaborator $configMock;

    public function let(
        Client $httpClient,
        Config $config
    ): void {
        $this->httpClientMock = $httpClient;
        $this->configMock = $config;

        $this->beConstructedWith(
            $this->httpClientMock,
            $this->configMock,
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
                    'branch' => 'branch',
                    'webhook_url' => 'webhook_url',
                    'graphql_url' => 'graphql_url',
                ]
            ]
        ]);

        $this->configMock->get('tenant_id')->willReturn(1);

        $this->httpClientMock->post(
            Argument::that(fn ($uri) => $uri === ''),
            Argument::that(
                fn ($options) => $options['form_params']['token'] === 'token' &&
                $options['form_params']['ref'] === 'branch' &&
                $options['form_params']['variables[BUILD_MODE]'] === 'PREVIEW' &&
                $options['form_params']['variables[TENANT_ID]'] === 1 &&
                $options['form_params']['variables[WEBHOOK_URL]'] === 'webhook_url' &&
                $options['form_params']['variables[GRAPHQL_URL]'] === 'graphql_url'
            )
        )
            ->shouldBeCalledOnce()
            ->willReturn(new Response("OK", 201));

        $this->triggerPipeline();
    }
}
