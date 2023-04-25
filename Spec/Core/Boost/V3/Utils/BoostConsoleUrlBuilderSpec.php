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
        $guid = '123';
        $siteUrl = 'https://www.minds.com/';

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->build($boost)->shouldBe('https://www.minds.com/boost/boost-console?boostGuid=123');
    }

    public function it_should_build_a_a_url_with_extra_query_params(Boost $boost)
    {
        $guid = '123';
        $siteUrl = 'https://www.minds.com/';
        $extraParams = [
            'queryParam1' => 1,
            'queryParam2' => '2'
        ];

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);


        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->build($boost, $extraParams)->shouldBe('https://www.minds.com/boost/boost-console?boostGuid=123&queryParam1=1&queryParam2=2');
    }

    public function it_should_build_a_a_url_with_filters_with_extra_query_params()
    {
        $status = BoostStatus::REJECTED;
        $location = BoostTargetLocation::NEWSFEED;

        $extraParams = [
            'queryParam1' => 1,
            'queryParam2' => '2'
        ];

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('https://www.minds.com/');

        $this->buildWithFilters($status, $location, $extraParams)->shouldBe('https://www.minds.com/boost/boost-console?state=rejected&location=feed&queryParam1=1&queryParam2=2');
    }
}
