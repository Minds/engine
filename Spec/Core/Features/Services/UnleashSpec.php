<?php

namespace Spec\Minds\Core\Features\Services;

use Minds\Common\Repository\Response;
use Minds\Core\Config;
use Minds\Core\Features\Services\Unleash;
use Minds\Core\Features\Services\Unleash\Repository;
use Minds\Entities\User;
use Minds\UnleashClient\Entities\Context;
use Minds\UnleashClient\Factories\FeatureArrayFactory as UnleashFeatureArrayFactory;
use Minds\UnleashClient\Http\Client as UnleashClient;
use Minds\UnleashClient\Unleash as UnleashResolver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UnleashSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var Repository */
    protected $repository;

    /** @var UnleashResolver */
    protected $unleashResolver;

    /** @var UnleashFeatureArrayFactory */
    protected $unleashFeatureArrayFactory;

    /** @var Unleash\ClientFactory */
    protected $unleashClientFactory;

    public function let(
        Config $config,
        Repository $repository,
        UnleashResolver $unleashResolver,
        UnleashFeatureArrayFactory $unleashFeatureArrayFactory,
        Unleash\ClientFactory $unleashClientFactory
    ) {
        $this->config = $config;
        $this->repository = $repository;
        $this->unleashResolver = $unleashResolver;
        $this->unleashFeatureArrayFactory = $unleashFeatureArrayFactory;
        $this->unleashClientFactory = $unleashClientFactory;

        $this->beConstructedWith(
            $config,
            $repository,
            $unleashResolver,
            $unleashFeatureArrayFactory,
            $unleashClientFactory
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Unleash::class);
    }

    public function it_should_sync(
        UnleashClient $client
    ) {
        $this->unleashClientFactory->build('phpspec')
            ->shouldBeCalled()
            ->willReturn($client);

        $client->register()
            ->shouldBeCalled()
            ->willReturn(true);

        $client->fetch()
            ->shouldBeCalled()
            ->willReturn([
                ['name' => 'feature1'],
                ['name' => 'feature2'],
            ]);

        $this->repository->add(Argument::type(Unleash\Entity::class))
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this
            ->setEnvironment('phpspec')
            ->sync(30)
            ->shouldReturn(true);
    }

    public function it_should_fetch(
        Response $response
    ) {
        $featuresMock = ['featuresMock' . mt_rand()];
        $resolvedFeaturesMock = ['resolvedFeaturesMock' . mt_rand()];

        $this->repository->getAllData([
            'environment' => 'phpspec'
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $response->toArray()
            ->shouldBeCalled()
            ->willReturn($featuresMock);

        $this->unleashFeatureArrayFactory->build($featuresMock)
            ->shouldBeCalled()
            ->willReturn($resolvedFeaturesMock);

        $this->unleashResolver->setFeatures($resolvedFeaturesMock)
            ->shouldBeCalled()
            ->willReturn($this->unleashResolver);

        $this->unleashResolver->setContext(Argument::that(function (Context $context) {
            return
                $context->getUserId() === null &&
                $context->getUserGroups() === ['anonymous']
                ;
        }))
            ->shouldBeCalled()
            ->willReturn($this->unleashResolver);

        $this->unleashResolver->export()
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => true,
                'feature4' => true,
                'feature5' => false,
                'feature6' => false,
                'unused-feature' => true,
            ]);

        $this
            ->setEnvironment('phpspec')
            ->fetch([
                'feature1',
                'feature2',
                'feature3',
                'feature4',
                'feature5',
                'feature6',
            ])
            ->shouldReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => true,
                'feature4' => true,
                'feature5' => false,
                'feature6' => false,
            ]);
    }

    public function it_should_fetch_with_user(
        User $user,
        Response $response
    ) {
        $featuresMock = ['featuresMock' . mt_rand()];
        $resolvedFeaturesMock = ['resolvedFeaturesMock' . mt_rand()];

        $this->repository->getAllData([
            'environment' => 'phpspec'
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $response->toArray()
            ->shouldBeCalled()
            ->willReturn($featuresMock);

        $this->unleashFeatureArrayFactory->build($featuresMock)
            ->shouldBeCalled()
            ->willReturn($resolvedFeaturesMock);

        $this->unleashResolver->setFeatures($resolvedFeaturesMock)
            ->shouldBeCalled()
            ->willReturn($this->unleashResolver);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $user->isAdmin()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->isCanary()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->isPlus()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->isPro()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->unleashResolver->setContext(Argument::that(function (Context $context) {
            return
                $context->getUserId() === '1000' &&
                $context->getUserGroups() === ['authenticated', 'admin', 'canary', 'pro', 'plus']
                ;
        }))
            ->shouldBeCalled()
            ->willReturn($this->unleashResolver);

        $this->unleashResolver->export()
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => true,
                'feature4' => true,
                'feature5' => false,
                'feature6' => false,
                'unused-feature' => true,
            ]);

        $this
            ->setEnvironment('phpspec')
            ->setUser($user)
            ->fetch([
                'feature1',
                'feature2',
                'feature3',
                'feature4',
                'feature5',
                'feature6',
            ])
            ->shouldReturn([
                'feature1' => true,
                'feature2' => false,
                'feature3' => true,
                'feature4' => true,
                'feature5' => false,
                'feature6' => false,
            ]);
    }
}
