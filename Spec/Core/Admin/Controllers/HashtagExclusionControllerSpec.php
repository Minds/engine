<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Admin\Controllers;

use Minds\Core\Admin\Controllers\HashtagExclusionController;
use Minds\Core\Admin\Services\HashtagExclusionService;
use Minds\Core\Admin\Types\HashtagExclusion\HashtagExclusionNode;
use Minds\Core\Admin\Types\HashtagExclusion\HashtagExclusionsConnection;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class HashtagExclusionControllerSpec extends ObjectBehavior
{
    private Collaborator $exclusionServiceMock;

    public function let(HashtagExclusionService $exclusionServiceMock)
    {
        $this->exclusionServiceMock = $exclusionServiceMock;
        $this->beConstructedWith($this->exclusionServiceMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(HashtagExclusionController::class);
    }

    public function it_should_get_hashtag_exclusions()
    {
        $first = 10;
        $after = 5;

        $exclusion1 = new HashtagExclusionNode(
            tenantId: 1,
            tag: 'test',
            adminGuid: 1,
            createdTimestamp: 1000,
        );
        $exclusion2 = new HashtagExclusionNode(
            tenantId: 1,
            tag: 'test2',
            adminGuid: 2,
            createdTimestamp: 2000,
        );

        $exclusion2->createdTimestamp = 2000;

        $this->exclusionServiceMock->getExcludedHashtags(
            after: $after,
            limit: $first,
            hasNextPage: Argument::any(),
        )
            ->willReturn(new \ArrayIterator([$exclusion1, $exclusion2]));

        $result = $this->getHashtagExclusions($first, $after);

        $result->shouldBeAnInstanceOf(HashtagExclusionsConnection::class);
    }

    public function it_should_exclude_hashtag(User $loggedInUser)
    {
        $hashtag = 'test';
        $this->exclusionServiceMock->excludeHashtag($hashtag, $loggedInUser)->willReturn(true);

        $this->excludeHashtag($hashtag, $loggedInUser)->shouldBe(true);
    }

    public function it_should_remove_hashtag_exclusion()
    {
        $hashtag = 'test';
        $this->exclusionServiceMock->removeHashtagExclusion($hashtag)->willReturn(true);

        $this->removeHashtagExclusion($hashtag)->shouldBe(true);
    }
}
