<?php

namespace Spec\Minds\Core\Hashtags\User;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Hashtags\Trending\Repository as TrendingRepository;
use Minds\Core\Hashtags\User\Manager;
use Minds\Core\Hashtags\User\PseudoHashtags;
use Minds\Core\Hashtags\User\Repository;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repository;
    private Collaborator $trendingRepository;
    private Collaborator $cacher;
    private Collaborator $config;
    private Collaborator $pseudoHashtags;
    private Collaborator $experimentsManager;

    public function let(
        Repository $repository = null,
        TrendingRepository $trendingRepository = null,
        abstractCacher $cacher = null,
        Config $config = null,
        PseudoHashtags $pseudoHashtags = null,
        ExperimentsManager $experimentsManager = null
    ) {
        $this->repository = $repository;
        $this->trendingRepository = $trendingRepository;
        $this->cacher = $cacher;
        $this->config = $config;
        $this->pseudoHashtags = $pseudoHashtags;
        $this->experimentsManager = $experimentsManager;

        $this->beConstructedWith(
            $this->repository,
            $this->trendingRepository,
            $this->cacher,
            $this->config,
            $this->pseudoHashtags,
            $this->experimentsManager
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_a_random_user_selected_tag(User $user)
    {
        $userGuid = '1234567890123456';

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $user->getLanguage()
            ->shouldBeCalled()
            ->willReturn('en');

        $this->cacher->get("hashtags::user-selected::$userGuid")
            ->shouldBeCalled()
            ->willReturn(json_encode([['hashtag' => 'minds1']]));

        $this->setUser($user)->getRandomUserSelectedTag()
            ->shouldBe('minds1');
    }
}
