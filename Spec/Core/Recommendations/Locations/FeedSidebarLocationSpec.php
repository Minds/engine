<?php

namespace Spec\Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\SuggestedChannels\SuggestedChannelsRecommendationsAlgorithm;
use Minds\Core\Recommendations\Locations\FeedSidebarLocation;
use Minds\Core\Suggestions\Manager as SuggestionsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FeedSidebarLocationSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(FeedSidebarLocation::class);
    }

    public function it_should_return_list_of_channel_suggestions()
    {
        $this->setUser(new User(Argument::any()));

        $response = $this->getLocationRecommendationsAlgorithm();

        $response->shouldBeAnInstanceOf(SuggestedChannelsRecommendationsAlgorithm::class);
    }
}
