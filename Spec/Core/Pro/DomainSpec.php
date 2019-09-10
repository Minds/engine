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

    function let(
        Config $config,
        Repository $repository
    )
    {
        $this->config = $config;
        $this->repository = $repository;

        $this->beConstructedWith($config, $repository);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Domain::class);
    }

    function it_should_lookup_for_a_domain(
        Response $repositoryResponse,
        Settings $settings
    )
    {
        $this->config->get('root_domains')
            ->shouldBeCalled()
            ->willReturn(['minds.com']);

        $this->repository->getList([
            'domain' => 'minds.test',
        ])
            ->shouldBeCalled()
            ->willReturn($repositoryResponse);
        
        $repositoryResponse->first()
            ->shouldBeCalled()
            ->willReturn($settings);

        $this
            ->lookup('minds.test')
            ->shouldReturn($settings);
    }

    function it_should_get_an_icon(
        Settings $settings,
        User $user
    )
    {
        $user->getIconURL('large')
            ->shouldBeCalled()
            ->willReturn('/1000/large');

        $this->getIcon($settings, $user)
            ->shouldReturn('/1000/large');
    }
}
