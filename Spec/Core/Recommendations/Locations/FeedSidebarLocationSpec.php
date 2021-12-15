<?php

namespace Spec\Minds\Core\Recommendations\Locations;

use Minds\Common\Repository\Response;
use Minds\Core\Recommendations\Locations\FeedSidebarLocation;
use Minds\Core\Suggestions\Manager as SuggestionsManager;
use Minds\Core\Suggestions\Suggestion;
use Minds\Entities\Entity;
use PhpSpec\ObjectBehavior;

class FeedSidebarLocationSpec extends ObjectBehavior
{
    public function getMatchers(): array
    {
        return  [
            'containValueLike' => function ($subject, $value) {
                foreach ($subject as $item) {
                    if ($item == $value) {
                        return true;
                    }
                }
                return false;
            }
        ];
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(FeedSidebarLocation::class);
    }

    public function it_should_return_list_of_channel_suggestions(
        SuggestionsManager $suggestionsManager
    ) {
        $expectedRecommendations = (new Suggestion())
            ->setEntityGuid(1)
            ->setEntityType("user")
            ->setEntity(new Entity(1));

        $suggestionsManager
            ->getList()
            ->shouldBeCalledOnce()
            ->willReturn(
                new Response([
                        $expectedRecommendations
                ])
            );

        $this->beConstructedWith($suggestionsManager);

        $response = $this->getRecommendations();

        $response->shouldContainValueLike($expectedRecommendations);
    }
}
