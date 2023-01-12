<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3\Utils;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class BoostConsoleUrlBuilderSpec extends ObjectBehavior
{
    private Collaborator $config;

    public function let(
        Config $config
    ): void {
        $this->config = $config;
        $this->beConstructedWith($this->config);
    }

    public function it_should_build_a_a_url(Boost $boost)
    {
        $status = BoostStatus::APPROVED;
        $location = BoostTargetLocation::NEWSFEED;
        $siteUrl = 'https://www.minds.com/';

        $boost->getStatus()
            ->shouldBeCalled()
            ->willReturn($status);

        $boost->getTargetLocation()
            ->shouldBeCalled()
            ->willReturn($location);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);
            
        $this->build($boost)->shouldBe('https://www.minds.com/boost/boost-console?state=approved&location=newsfeed');
    }

    public function it_should_build_a_a_url_with_extra_query_params(Boost $boost)
    {
        $status = BoostStatus::PENDING;
        $location = BoostTargetLocation::SIDEBAR;
        $siteUrl = 'https://www.minds.com/';
        $extraParams = [
            'queryParam1' => 1,
            'queryParam2' => '2'
        ];

        $boost->getStatus()
            ->shouldBeCalled()
            ->willReturn($status);

        $boost->getTargetLocation()
            ->shouldBeCalled()
            ->willReturn($location);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->build($boost, $extraParams)->shouldBe('https://www.minds.com/boost/boost-console?state=pending&location=sidebar&queryParam1=1&queryParam2=2');
    }
}
