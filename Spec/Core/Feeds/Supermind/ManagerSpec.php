<?php

declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\Supermind;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;
use Minds\Core\Feeds\Supermind\Manager;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $elasticSearchManagerMock;
    private Collaborator $configMock;

    public function let(
        ElasticSearchManager $elasticSearchManagerMock,
        Config $configMock
    ) {
        $this->elasticSearchManagerMock = $elasticSearchManagerMock;
        $this->configMock = $configMock;
        $this->beConstructedWith($this->elasticSearchManagerMock, $this->configMock);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_supermind_activities_with_no_excluded_owner_guids(
        Response $response
    ): void {
        $this->configMock->get('supermind')
            ->shouldBeCalled()
            ->willReturn(['excluded_user_guids' => []]);

        $this->elasticSearchManagerMock
            ->getList([
                'limit' => 12,
                'type' => 'activity',
                'algorithm' => 'latest',
                'single_owner_threshold' => 0,
                'period' => 'all', // legacy option
                'to_timestamp' => null,
                'from_timestamp' => null,
                'supermind' => true,
                'exclude_owner_guids' => []
            ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->getSupermindActivities()
            ->shouldBe($response);
    }

    public function it_should_get_supermind_activities_with_excluded_owner_guids(
        Response $response
    ): void {
        $userGuid1 = '1234567890';
        $userGuid2 = '1234567891';
        $this->configMock->get('supermind')
            ->shouldBeCalled()
            ->willReturn(['excluded_user_guids' => [$userGuid1, $userGuid2]]);

        $this->elasticSearchManagerMock
            ->getList([
                'limit' => 12,
                'type' => 'activity',
                'algorithm' => 'latest',
                'single_owner_threshold' => 0,
                'period' => 'all', // legacy option
                'to_timestamp' => null,
                'from_timestamp' => null,
                'supermind' => true,
                'exclude_owner_guids' => [$userGuid1, $userGuid2]
            ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->getSupermindActivities()
            ->shouldBe($response);
    }
}
