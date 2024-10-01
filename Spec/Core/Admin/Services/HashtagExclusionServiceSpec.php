<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Admin\Services;

use Minds\Core\Admin\Services\HashtagExclusionService;
use Minds\Core\Admin\Repositories\HashtagExclusionRepository;
use Minds\Core\Hashtags\Trending\Cache;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class HashtagExclusionServiceSpec extends ObjectBehavior
{
    private Collaborator $hashtagExclusionRepositoryMock;
    private Collaborator $cacheMock;

    public function let(HashtagExclusionRepository $hashtagExclusionRepositoryMock, Cache $cacheMock)
    {
        $this->hashtagExclusionRepositoryMock = $hashtagExclusionRepositoryMock;
        $this->cacheMock = $cacheMock;
        $this->beConstructedWith($this->hashtagExclusionRepositoryMock, $this->cacheMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(HashtagExclusionService::class);
    }

    public function it_should_exclude_hashtag(User $loggedInUser)
    {
        $hashtag = 'test';
        $userGuid = 123;
        $loggedInUser->getGuid()->willReturn($userGuid);

        $this->hashtagExclusionRepositoryMock->upsertTag(
            tag: $hashtag,
            adminGuid: $userGuid
        )->willReturn(true);

        $this->cacheMock->invalidate()
            ->shouldBeCalled()
            ->willReturn($this->cacheMock);

        $this->excludeHashtag($hashtag, $loggedInUser)->shouldReturn(true);
    }

    public function it_should_handle_failure_to_exclude_hashtag(User $loggedInUser)
    {
        $hashtag = 'test';
        $userGuid = 123;
        $loggedInUser->getGuid()->willReturn($userGuid);

        $this->hashtagExclusionRepositoryMock->upsertTag(
            tag: $hashtag,
            adminGuid: $userGuid
        )->willReturn(false);

        $this->cacheMock->invalidate()
            ->shouldNotBeCalled();

        $this->excludeHashtag($hashtag, $loggedInUser)->shouldReturn(false);
    }

    public function it_should_remove_hashtag_exclusion()
    {
        $hashtag = 'test';

        $this->hashtagExclusionRepositoryMock->removeTag($hashtag)->willReturn(true);

        $this->cacheMock->invalidate()
            ->shouldBeCalled()
            ->willReturn($this->cacheMock);

        $this->removeHashtagExclusion($hashtag)->shouldReturn(true);
    }

    public function it_should_handle_failure_to_remove_hashtag_exclusion()
    {
        $hashtag = 'test';

        $this->hashtagExclusionRepositoryMock->removeTag($hashtag)->willReturn(false);

        $this->cacheMock->invalidate()
            ->shouldNotBeCalled();
        
        $this->removeHashtagExclusion($hashtag)->shouldReturn(false);
    }
}
