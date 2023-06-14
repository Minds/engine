<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Delegates;

use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Twitter\Exceptions\TwitterDetailsNotFoundException;
use Minds\Core\Twitter\Manager as TwitterManager;
use Minds\Entities\Activity;
use Minds\Entities\User;

class TwitterEventsDelegate
{
    public function __construct(
        private ?TwitterManager $twitterManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?MindsConfig $mindsConfig = null,
        private ?Logger $logger = null
    ) {
        $this->twitterManager ??= Di::_()->get('Twitter\Manager');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @param SupermindRequest $supermindRequest
     * @return void
     * @throws TwitterDetailsNotFoundException
     */
    public function onAcceptSupermindOffer(SupermindRequest $supermindRequest): void
    {
        if (!$supermindRequest->getTwitterRequired()) {
            return;
        }

        $activityMindsUrl = $this->getActivityMindsUrl($supermindRequest->getReplyActivityGuid()) . $this->getAnalyticsParameters();

        /**
         * @type Activity $activityPost
         */
        $activityPost = $this->entitiesBuilder->single($supermindRequest->getReplyActivityGuid());
        $content = substr($activityPost->getMessage(), 0, 253);
        $content .= strlen($activityPost->getMessage()) > 253 ? '...' : ' ';
        $content .= $activityMindsUrl;

        $this->logger->info("tweet message length: " . strlen($content));

        $this->twitterManager
            ->setUser($this->buildUser($supermindRequest->getReceiverGuid()))
            // The below method call will trigger the TwitterDetailsNotFoundException if the user has no Twitter details provided.
            ->postTextTweet($content);
    }

    private function getActivityMindsUrl(string $activityGuid): string
    {
        return $this->mindsConfig->get('site_url') . "newsfeed/$activityGuid";
    }

    private function getAnalyticsParameters(): string
    {
        return "?utm_source=twitter&utm_medium=supermind&utm_campaign=repost";
    }

    private function buildUser(string $userGuid): User
    {
        return $this->entitiesBuilder->single($userGuid);
    }
}
