<?php

namespace Spec\Minds\Core\Rewards;

use Minds\Core\Rewards\Notify;
use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\TokenPrices;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Log\Logger;
use Minds\Core\Rewards\Repository;
use Minds\Core\Rewards\RewardEntry;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotifySpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var TokenPrices\Manager */
    protected $tokenPricesManager;

    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var Logger */
    protected $logger;

    public function let(
        Repository $repository,
        TokenPrices\Manager $tokenPricesManager,
        EventsDispatcher $eventsDispatcher
    ) {
        $this->beConstructedWith($repository, $tokenPricesManager, $eventsDispatcher);
        $this->repository = $repository;
        $this->tokenPricesManager = $tokenPricesManager;
        $this->eventsDispatcher = $eventsDispatcher;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Notify::class);
    }

    public function it_should_send_usd_notification()
    {
        $this->repository->getIterator(Argument::any())
            ->willReturn([
                (new RewardEntry())
                    ->setUserGuid('123')
                    ->setTokenAmount(BigDecimal::of(5)),
                (new RewardEntry())
                    ->setUserGuid('123')
                    ->setTokenAmount(BigDecimal::of(2)),
            ]);

        $this->tokenPricesManager->getPrices()
            ->willReturn([
                'minds' => 0.15,
            ]);

        $this->eventsDispatcher->trigger('notification', 'all', Argument::that(function ($payload) {
            return $payload['params']['amount'] === "7.000"
                && $payload['message'] === "🚀 You earned $1.05 worth of tokens yesterday. Nice job! 🚀";
        }))
            ->shouldBeCalled();

        $this->run();
    }

    public function it_should_send_token_notification()
    {
        $this->repository->getIterator(Argument::any())
            ->willReturn([
                (new RewardEntry())
                    ->setUserGuid('123')
                    ->setTokenAmount(BigDecimal::of(0.01)),
                (new RewardEntry())
                    ->setUserGuid('123')
                    ->setTokenAmount(BigDecimal::of(0.01)),
            ]);

        $this->tokenPricesManager->getPrices()
            ->willReturn([
                'minds' => 0.15,
            ]);

        $this->eventsDispatcher->trigger('notification', 'all', Argument::that(function ($payload) {
            return $payload['params']['amount'] === "0.020"
                && $payload['message'] === "🚀 You earned 0.020 tokens yesterday 🚀";
        }))
            ->shouldBeCalled();

        $this->run();
    }

    public function it_should_send_not_send_notification()
    {
        $this->repository->getIterator(Argument::any())
            ->willReturn([
                (new RewardEntry())
                    ->setUserGuid('123')
                    ->setTokenAmount(BigDecimal::of(0.0001)),
                (new RewardEntry())
                    ->setUserGuid('123')
                    ->setTokenAmount(BigDecimal::of(0.0002)),
            ]);

        $this->tokenPricesManager->getPrices()
            ->willReturn([
                'minds' => 0.15,
            ]);

        $this->eventsDispatcher->trigger('notification', 'all', Argument::any())
            ->shouldNotBeCalled();

        $this->run();
    }
}
