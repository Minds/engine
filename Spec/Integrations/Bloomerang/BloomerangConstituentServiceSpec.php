<?php

namespace Spec\Minds\Integrations\Bloomerang;

use Minds\Core\Config\Config;
use Minds\Integrations\Bloomerang\BloomerangConstituentService;
use GuzzleHttp\Client;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Integrations\Bloomerang\Repository;
use PhpSpec\ObjectBehavior;

class BloomerangConstituentServiceSpec extends ObjectBehavior
{
    public function let(
        Config $configMock,
        Client $httpClientMock,
        SiteMembershipReaderService $siteMembershipReaderServiceMock,
        SiteMembershipSubscriptionsService $siteMembershipSubscriptionsServiceMock,
        Repository $repositoryMock,
    ) {
        $this->beConstructedWith($configMock, $httpClientMock, $siteMembershipReaderServiceMock, $siteMembershipSubscriptionsServiceMock, $repositoryMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BloomerangConstituentService::class);
    }
}
