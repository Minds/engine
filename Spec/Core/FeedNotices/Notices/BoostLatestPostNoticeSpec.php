<?php
namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\Config\Config;
use Minds\Core\FeedNotices\Notices\BoostLatestPostNotice;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;
use PhpSpec\Wrapper\Collaborator;

class BoostLatestPostNoticeSpec extends ObjectBehavior
{
    /** @var FeedsUserManager */
    protected $feedsUserManager;

    /** @var Config */
    private $config;

    public function let(
        FeedsUserManager $feedsUserManager,
        Config $config
    ) {
        $this->feedsUserManager = $feedsUserManager;
        $this->config = $config;

        $this->beConstructedWith(
            $feedsUserManager,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostLatestPostNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('boost-latest-post');
    }

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_that_notice_should_show_because_user_has_made_posts(
        User $user,
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);
    
        $this->feedsUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feedsUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedsUserManager);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_that_notice_should_not_show_because_user_has_not_made_posts(
        User $user,
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->feedsUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->feedsUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedsUserManager);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_that_notice_should_not_show_because_an_exception_was_thrown(
        User $user,
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->feedsUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willThrow(new \Exception());

        $this->feedsUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedsUserManager);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_this_is_a_tenant_context(
        User $user,
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn('123');

        $this->feedsUserManager->hasMadePosts()
            ->shouldNotBeCalled();

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_instance_after_setting_user(User $user)
    {
        $this->setUser($user)
            ->shouldBe($this);
    }

    public function it_should_export(User $user)
    {
        $this->feedsUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feedsUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedsUserManager);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'boost-latest-post',
            'location' => 'inline',
            'should_show' => true,
            'is_dismissible' => true
        ]);
    }
}
