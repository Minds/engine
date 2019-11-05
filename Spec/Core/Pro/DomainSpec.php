<?php

namespace Spec\Minds\Core\Pro;

use Minds\Common\Repository\Response;
use Minds\Core\Config;
use Minds\Core\Pro\Domain;
use Minds\Core\Pro\Repository;
use Minds\Core\Pro\Settings;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DomainSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var Repository */
    protected $repository;

    public function let(
        Config $config,
        Repository $repository
    ) {
        $this->config = $config;
        $this->repository = $repository;

        $this->beConstructedWith($config, $repository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Domain::class);
    }

    public function it_should_lookup(
        Response $getListResponse,
        Settings $settings
    ) {
        $this->config->get('pro')
            ->shouldBeCalled()
            ->willReturn([
                'root_domains' => ['phpspec.test']
            ]);

        $this->repository->getList([
            'domain' => 'phpspec-test.com'
        ])
            ->shouldBeCalled()
            ->willReturn($getListResponse);

        $getListResponse->first()
            ->shouldBeCalled()
            ->willReturn($settings);

        $this
            ->lookup('phpspec-test.com')
            ->shouldReturn($settings);
    }

    public function it_should_not_lookup_if_root_domain()
    {
        $this->config->get('pro')
            ->shouldBeCalled()
            ->willReturn([
                'root_domains' => ['phpspec.test']
            ]);

        $this->repository->getList(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->lookup('phpspec.test')
            ->shouldReturn(null);
    }

    public function it_should_check_if_domain_is_unavailable(
        Response $getListResponse,
        Settings $settings
    ) {
        $this->config->get('pro')
            ->shouldBeCalled()
            ->willReturn([
                'root_domains' => ['phpspec.test']
            ]);

        $this->repository->getList([
            'domain' => 'phpspec-test.com'
        ])
            ->shouldBeCalled()
            ->willReturn($getListResponse);

        $getListResponse->first()
            ->shouldBeCalled()
            ->willReturn($settings);

        $settings->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1001);

        $this
            ->isAvailable('phpspec-test.com', 1000)
            ->shouldReturn(false);
    }

    public function it_should_check_if_domain_is_available_if_same_owner(
        Response $getListResponse,
        Settings $settings
    ) {
        $this->config->get('pro')
            ->shouldBeCalled()
            ->willReturn([
                'root_domains' => ['phpspec.test']
            ]);

        $this->repository->getList([
            'domain' => 'phpspec-test.com'
        ])
            ->shouldBeCalled()
            ->willReturn($getListResponse);

        $getListResponse->first()
            ->shouldBeCalled()
            ->willReturn($settings);

        $settings->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this
            ->isAvailable('phpspec-test.com', 1000)
            ->shouldReturn(true);
    }

    public function it_should_check_if_domain_is_available(
        Response $getListResponse
    ) {
        $this->config->get('pro')
            ->shouldBeCalled()
            ->willReturn([
                'root_domains' => ['phpspec.test']
            ]);

        $this->repository->getList([
            'domain' => 'phpspec-test.com'
        ])
            ->shouldBeCalled()
            ->willReturn($getListResponse);

        $getListResponse->first()
            ->shouldBeCalled()
            ->willReturn(null);

        $this
            ->isAvailable('phpspec-test.com', 1000)
            ->shouldReturn(true);
    }

    public function it_should_return_as_unavailable_if_root_domain()
    {
        $this->config->get('pro')
            ->shouldBeCalled()
            ->willReturn([
                'root_domains' => ['phpspec.test']
            ]);

        $this->repository->getList(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->isAvailable('phpspec.test', 1000)
            ->shouldReturn(false);
    }

    public function it_should_get_icon(
        Settings $settings,
        User $owner
    ) {
        $owner->getIconURL(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn('http://phpspec/icon');

        $this
            ->getIcon($settings, $owner)
            ->shouldReturn('http://phpspec/icon');
    }
}
