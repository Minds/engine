<?php

namespace Spec\Minds\Core\Analytics\TenantAdminAnalytics\Services;

use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsResolutionEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Services\AdminAnalyticsFetchService;
use Minds\Core\Analytics\TenantAdminAnalytics\Repository;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsChartType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsKpiType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart\AnalyticsChartBucketType;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Votes;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class AdminAnalyticsFetchServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $entitiesBuilderMock;

    public function let(Repository $repositoryMock, EntitiesBuilder $entitiesBuilderMock, Votes\Manager $votesManagerMock)
    {
        $this->beConstructedWith($repositoryMock, $entitiesBuilderMock, $votesManagerMock);
        $this->repositoryMock = $repositoryMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AdminAnalyticsFetchService::class);
    }

    public function it_should_return_chart_with_day_resolution(AnalyticsChartBucketType $bucket)
    {
        $this->repositoryMock->getBucketsByMetric(AnalyticsMetricEnum::DAILY_ACTIVE_USERS, AnalyticsResolutionEnum::DAY, strtotime('midnight yesterday'), strtotime('midnight'))
            ->willReturn([ $bucket ]);
    
        $this->getChart(AnalyticsMetricEnum::DAILY_ACTIVE_USERS, strtotime('midnight yesterday'), strtotime('midnight'))
            ->shouldBeAnInstanceOf(AnalyticsChartType::class);
    }

    public function it_should_return_chart_with_month_resolution(AnalyticsChartBucketType $bucket)
    {
        $this->repositoryMock->getBucketsByMetric(AnalyticsMetricEnum::DAILY_ACTIVE_USERS, AnalyticsResolutionEnum::MONTH, strtotime('midnight 60 days ago'), strtotime('midnight'))
            ->willReturn([ $bucket ]);
    
        $this->getChart(AnalyticsMetricEnum::DAILY_ACTIVE_USERS, strtotime('midnight 60 days ago'), strtotime('midnight'))
            ->shouldBeAnInstanceOf(AnalyticsChartType::class);
    }

    public function it_should_return_kpis(AnalyticsKpiType $kpiMock)
    {
        $this->repositoryMock->getKpis([
            AnalyticsMetricEnum::DAILY_ACTIVE_USERS,
            AnalyticsMetricEnum::MEAN_SESSION_SECS
        ], strtotime('midnight yesterday'), strtotime('midnight'))->willReturn([$kpiMock, $kpiMock]);
        
        $kpis = $this->getKpis([
            AnalyticsMetricEnum::DAILY_ACTIVE_USERS,
            AnalyticsMetricEnum::MEAN_SESSION_SECS
        ], strtotime('midnight yesterday'), strtotime('midnight'));
        $kpis->shouldHaveCount(2);
        $kpis[0]->shouldBeAnInstanceOf(AnalyticsKpiType::class);
        $kpis[1]->shouldBeAnInstanceOf(AnalyticsKpiType::class);
    }

    public function it_should_return_popular_activities(Activity $activityMock)
    {
        $this->repositoryMock->getPopularActivity(0, strtotime('midnight yesterday'), strtotime('midnight'))
            ->willYield([
                [
                    'guid' => '123',
                    'votes' => '57'
                ],
                [
                    'guid' => '456',
                    'votes' => '2'
                ]
            ]);

        $this->entitiesBuilderMock->single('123')->willReturn($activityMock);
        $this->entitiesBuilderMock->single('456')->willReturn($activityMock);

        $activityMock->getImpressions()->willReturn(100);

        $response = $this->getPopularActivityNodes(strtotime('midnight yesterday'), strtotime('midnight'));
        $response->shouldHaveCount(2);

        $response[0]->views->shouldBe(100);
        $response[0]->engagements->shouldBe(57);
        $response[1]->views->shouldBe(100);
        $response[1]->engagements->shouldBe(2);
    }

    public function it_should_return_popular_groups(Group $groupMock)
    {
        $this->repositoryMock->getPopularGroups(0, strtotime('midnight yesterday'), strtotime('midnight'))
            ->willYield([
                [
                    'guid' => '123',
                    'new_members' => '57'
                ],
                [
                    'guid' => '456',
                    'new_members' => '2'
                ]
            ]);

        $this->entitiesBuilderMock->single('123')->willReturn($groupMock);
        $this->entitiesBuilderMock->single('456')->willReturn($groupMock);

        $response = $this->getPopularGroupNodes(strtotime('midnight yesterday'), strtotime('midnight'));
        $response->shouldHaveCount(2);

        $response[0]->newMembers->shouldBe(57);
        $response[1]->newMembers->shouldBe(2);
    }

    public function it_should_return_popular_users(User $userMock)
    {
        $this->repositoryMock->getPopularUsers(0, strtotime('midnight yesterday'), strtotime('midnight'))
            ->willYield([
                [
                    'guid' => '123',
                    'subscribers' => '57'
                ],
                [
                    'guid' => '456',
                    'subscribers' => '2'
                ]
            ]);

        $this->entitiesBuilderMock->single('123')->willReturn($userMock);
        $this->entitiesBuilderMock->single('456')->willReturn($userMock);

        $userMock->getSubscribersCount()->willReturn(100);

        $response = $this->getPopularUserNodes(strtotime('midnight yesterday'), strtotime('midnight'));
        $response->shouldHaveCount(2);

        $response[0]->totalSubscribers->shouldBe(100);
        $response[0]->newSubscribers->shouldBe(57);
        $response[1]->totalSubscribers->shouldBe(100);
        $response[1]->newSubscribers->shouldBe(2);
    }
}
