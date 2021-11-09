<?php

namespace Minds\Core\Security\RateLimits;

class InteractionsLimiter
{
    /** @var KeyValueLimiter */
    protected $kvLimiter;

    /** @var RateLimit[] */
    protected $maps = [];

    public function __construct($kvLimiter = null)
    {
        $this->kvLimiter = $kvLimiter ?? new KeyValueLimiter();
        $this->maps = [
            (new RateLimit)
                ->setKey('subscribe')
                ->setSeconds(300)
                ->setMax(50),
            (new RateLimit)
                ->setKey('subscribe')
                ->setSeconds(3600)
                ->setMax(200),
            (new RateLimit)
                ->setKey('subscribe')
                ->setSeconds(86400)
                ->setMax(400),

            (new RateLimit)
                ->setKey('voteup')
                ->setSeconds(300)
                ->setMax(150),
            (new RateLimit)
                ->setKey('voteup')
                ->setSeconds(86400)
                ->setMax(1000),

            (new RateLimit)
                ->setKey('votedown')
                ->setSeconds(300)
                ->setMax(150),
            (new RateLimit)
                ->setKey('votedown')
                ->setSeconds(86400)
                ->setMax(1000),

            (new RateLimit)
                ->setKey('comment')
                ->setSeconds(300)
                ->setMax(75),
            (new RateLimit)
                ->setKey('comment')
                ->setSeconds(86400)
                ->setMax(500),

            (new RateLimit)
                ->setKey('remind')
                ->setSeconds(86400)
                ->setMax(500),
        ];
    }

    /**
     * Checks and increments rate limits for an interaction
     * @param string $userGuid
     * @param string $interaction
     * @return void
     */
    public function checkAndIncrement(string $userGuid, string $interaction): void
    {
        $rateLimits = $this->getRateLimitsByInteraction($interaction);

        $this->kvLimiter->setKey($interaction)
            ->setValue($userGuid)
            ->setRateLimits($rateLimits)
            ->checkAndIncrement();
    }

    /**
     * Returns the smallest remaining attempts a user has for an interaction
     * @param string $userGuid
     * @param string $interaction
     * @return int
     */
    public function getRemainingAttempts(string $userGuid, string $interaction): int
    {
        $rateLimits = $this->kvLimiter->setKey($interaction)
            ->setValue($userGuid)
            ->setRateLimits($this->getRateLimitsByInteraction($interaction))
            ->getRateLimitsWithRemainings();

        $remainingAttempts = array_reduce(
            $rateLimits,
            function ($carry, $rateLimit) {
                return min($rateLimit->getRemaining() ?: INF, $carry);
            },
            INF
        );

        return $remainingAttempts;
    }

    /**
     * Returns rate limits set for an interaction
     * @param string $interaction
     * @return RateLimit[]
     */
    private function getRateLimitsByInteraction(string $interaction): array
    {
        $rateLimits = [];
        foreach ($this->maps as $rateLimit) {
            if ($rateLimit->getKey() === $interaction) {
                $rateLimits[] = $rateLimit;
            }
        }
        return $rateLimits;
    }
}
