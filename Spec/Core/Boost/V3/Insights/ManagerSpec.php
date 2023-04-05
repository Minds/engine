<?php

namespace Spec\Minds\Core\Boost\V3\Insights;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Insights\Manager;
use Minds\Core\Boost\V3\Insights\Repository;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    private $repositoryMock;

    public function let(Repository $repositoryMock)
    {
        $this->beConstructedWith($repositoryMock);
        $this->repositoryMock = $repositoryMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_calculate_estimate_safe_newsfeed_cash()
    {
        $data = array_map(function ($val) {
            return (float) $val[0];
        }, array_map('str_getcsv', file(dirname(__FILE__) . '/cash_newsfeed_safe.csv')));

        $this->repositoryMock->getHistoricCpms(BoostTargetAudiences::SAFE, BoostTargetLocation::NEWSFEED, BoostPaymentMethod::CASH)
            ->willReturn($data);

        $estimate = $this->getEstimate(BoostTargetAudiences::SAFE, BoostTargetLocation::NEWSFEED, BoostPaymentMethod::CASH, 10, 1);
        $estimate['views']->shouldBeLike([
            'low' => 4000,
            'high' => 8700,
        ]);
    }

    public function it_should_calculate_estimate_safe_newsfeed_tokens()
    {
        $data = array_map(function ($val) {
            return (float) $val[0];
        }, array_map('str_getcsv', file(dirname(__FILE__) . '/token_newsfeed_safe.csv')));

        $this->repositoryMock->getHistoricCpms(BoostTargetAudiences::SAFE, BoostTargetLocation::NEWSFEED, BoostPaymentMethod::CASH)
            ->willReturn($data);

        $estimate = $this->getEstimate(BoostTargetAudiences::SAFE, BoostTargetLocation::NEWSFEED, BoostPaymentMethod::CASH, 5, 1);
        $estimate['views']->shouldBeLike([
            'low' => 200,
            'high' => 900,
        ]);
    }
}
