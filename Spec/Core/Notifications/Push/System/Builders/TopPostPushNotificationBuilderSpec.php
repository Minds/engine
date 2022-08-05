<?php

namespace Spec\Minds\Core\Notifications\Push\System\Builders;

use Minds\Core\Config;
use PhpSpec\ObjectBehavior;
use Minds\Core\Blogs\Blog;
use Minds\Core\Notifications\Push\System\Builders\TopPostPushNotificationBuilder;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;

class TopPostPushNotificationBuilderSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    public function let(
        Config $config = null
    ) {
        $this->beConstructedWith($config);
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TopPostPushNotificationBuilder::class);
    }

    public function it_should_build_a_text_activity_notification(
        Activity $activity,
        User $user
    ) {
        $activity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $activity->getPermaURL()
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $activity->getMessage()
            ->shouldBeCalled()
            ->willReturn('body');

        $activity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entity = $activity;

        $builtNotification = $this->build($activity);

        $builtNotification->getTitle()->shouldBe('Test posted');
        $builtNotification->getBody()->shouldBe('body');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('');
    }

    public function it_should_build_a_blog_notification(
        Blog $blog,
        User $user
    ) {
        $blog->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $blog->getSubtype()
            ->shouldBeCalled()
            ->willReturn('blog');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $blog->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $blog->getTitle()
            ->shouldBeCalled()
            ->willReturn('blog-title');

        $blog->getPermaUrl()
            ->shouldBeCalled()
            ->willReturn('blog-url');

        $blog->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('icon-url');

        $this->entity = $blog;

        $builtNotification = $this->build($blog);

        $builtNotification->getTitle()->shouldBe('Test posted a blog');
        $builtNotification->getBody()->shouldBe('blog-title');
        $builtNotification->getUri()->shouldBe('blog-url');
        $builtNotification->getMedia()->shouldBe('icon-url');
    }

    public function it_should_build_an_image_notification_with_body_and_title(
        Image $image,
        User $user
    ) {
        $image->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $image->getSubtype()
            ->shouldBeCalled()
            ->willReturn('image');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $image->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $image->getTitle()
            ->shouldBeCalled()
            ->willReturn('image-title');

        $image->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $image->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('image-url');

        $this->entity = $image;

        $builtNotification = $this->build($image);

        $builtNotification->getTitle()->shouldBe('Test posted an image');
        $builtNotification->getBody()->shouldBe('image-title');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('image-url');
    }

    public function it_should_build_an_image_notification_with_body_and_no_title(
        Image $image,
        User $user
    ) {
        $image->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $image->getSubtype()
            ->shouldBeCalled()
            ->willReturn('image');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $image->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $image->getTitle()
            ->shouldBeCalled()
            ->willReturn('');

        $image->getDescription()
            ->shouldBeCalled()
            ->willReturn('image-description');

        $image->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $image->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('image-url');


        $this->entity = $image;

        $builtNotification = $this->build($image);

        $builtNotification->getTitle()->shouldBe('Test posted an image');
        $builtNotification->getBody()->shouldBe('image-description');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('image-url');
    }

    public function it_should_build_an_image_notification_with_no_body_but_has_a_title(
        Image $image,
        User $user
    ) {
        $image->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $image->getSubtype()
            ->shouldBeCalled()
            ->willReturn('image');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $image->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $image->getTitle()
            ->shouldBeCalled()
            ->willReturn('title-text');

        $image->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $image->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('image-url');

        $this->entity = $image;

        $builtNotification = $this->build($image);

        $builtNotification->getTitle()->shouldBe('Test posted an image');
        $builtNotification->getBody()->shouldBe('title-text');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('image-url');
    }

    public function it_should_build_an_image_notification_with_no_body_and_no_title(
        Image $image,
        User $user
    ) {
        $image->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $image->getSubtype()
            ->shouldBeCalled()
            ->willReturn('image');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $image->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $image->getTitle()
            ->shouldBeCalled()
            ->willReturn('');

        $image->getDescription()
            ->shouldBeCalled()
            ->willReturn('');

        $image->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $image->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('image-url');

        $this->entity = $image;

        $builtNotification = $this->build($image);

        $builtNotification->getTitle()->shouldBe('Test posted an image');
        $builtNotification->getBody()->shouldBe('');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('image-url');
    }

    public function it_should_build_a_video_notification_with_body_and_title(
        Video $video,
        User $user
    ) {
        $video->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $video->getSubtype()
            ->shouldBeCalled()
            ->willReturn('video');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $video->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $video->getTitle()
            ->shouldBeCalled()
            ->willReturn('video-title');

        $video->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $video->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('video-thumbnail-url');

        $this->entity = $video;

        $builtNotification = $this->build($video);

        $builtNotification->getTitle()->shouldBe('Test posted a video');
        $builtNotification->getBody()->shouldBe('video-title');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('video-thumbnail-url');
    }

    public function it_should_build_an_video_notification_with_body_and_no_title(
        Image $image,
        Video $video,
        User $user
    ) {
        $video->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $video->getSubtype()
            ->shouldBeCalled()
            ->willReturn('video');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $video->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $video->getTitle()
            ->shouldBeCalled()
            ->willReturn('');

        $video->getDescription()
            ->shouldBeCalled()
            ->willReturn('video-description');

        $video->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $video->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('video-thumbnail-url');

        $this->entity = $video;

        $builtNotification = $this->build($video);

        $builtNotification->getTitle()->shouldBe('Test posted a video');
        $builtNotification->getBody()->shouldBe('video-description');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('video-thumbnail-url');
    }

    public function it_should_build_an_video_notification_with_no_body_but_has_a_title(
        Video $video,
        User $user
    ) {
        $video->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $video->getSubtype()
            ->shouldBeCalled()
            ->willReturn('video');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');

        $user->getName()
            ->shouldBeCalled()
         ->willReturn('Test');

        $video->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $video->getTitle()
            ->shouldBeCalled()
            ->willReturn('title-text');

        $video->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $video->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('image-url');

        $this->entity = $video;

        $builtNotification = $this->build($video);

        $builtNotification->getTitle()->shouldBe('Test posted a video');
        $builtNotification->getBody()->shouldBe('title-text');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('image-url');
    }

    public function it_should_build_an_video_notification_with_no_body_and_no_title(
        Video $video,
        User $user
    ) {
        $video->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $video->getSubtype()
            ->shouldBeCalled()
            ->willReturn('video');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');


        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $video->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $video->getTitle()
            ->shouldBeCalled()
            ->willReturn('');

        $video->getDescription()
            ->shouldBeCalled()
            ->willReturn('');

        $video->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $video->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('image-url');

        $this->entity = $video;

        $builtNotification = $this->build($video);

        $builtNotification->getTitle()->shouldBe('Test posted a video');
        $builtNotification->getBody()->shouldBe('');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('image-url');
    }

    public function it_should_trim_long_titles(
        Blog $blog,
        User $user
    ) {
        $blog->getType()
            ->shouldBeCalled()
            ->willReturn('object');

        $blog->getSubtype()
            ->shouldBeCalled()
            ->willReturn('blog');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn(str_repeat("a", 100));
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn(str_repeat("A", 100));

        $blog->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $blog->getTitle()
            ->shouldBeCalled()
            ->willReturn('blog-title');

        $blog->getPermaUrl()
            ->shouldBeCalled()
            ->willReturn('blog-url');

        $blog->getIconUrl('large')
            ->shouldBeCalled()
            ->willReturn('icon-url');

        $this->entity = $blog;

        $builtNotification = $this->build($blog);

        $builtNotification->getTitle()->shouldBe(str_repeat("A", 45).'... posted a blog');
        $builtNotification->getBody()->shouldBe('blog-title');
        $builtNotification->getUri()->shouldBe('blog-url');
        $builtNotification->getMedia()->shouldBe('icon-url');
    }

    public function it_should_trim_long_bodies(
        Activity $activity,
        User $user
    ) {
        $activity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $activity->getPermaURL()
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $activity->getMessage()
            ->shouldBeCalled()
            ->willReturn(str_repeat("a", 200));

        $activity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entity = $activity;

        $builtNotification = $this->build($activity);

        $builtNotification->getTitle()->shouldBe('Test posted');
        $builtNotification->getBody()->shouldBe(str_repeat("a", 170).'...');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('');
    }

    public function it_should_use_rich_embed_title_if_it_was_a_link(
        Activity $activity,
        User $user
    ) {
        $activity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('Test');

        $activity->getPermaURL()
            ->shouldBeCalled()
            ->willReturn('http://test.com/test');

        $activity->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $activity->getTitle()
            ->shouldBeCalled()
            ->willReturn(str_repeat("T", 200));

        $activity->getMessage()
            ->shouldBeCalled()
            ->willReturn('http://test.com/test');

        $activity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entity = $activity;

        $builtNotification = $this->build($activity);

        $builtNotification->getTitle()->shouldBe('Test posted a link');
        $builtNotification->getBody()->shouldBe(str_repeat("T", 170).'...');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('');
    }

    public function it_should_use_username_if_name_wasnt_there(
        Activity $activity,
        User $user
    ) {
        $activity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('');

        $activity->getPermaURL()
            ->shouldBeCalled()
            ->willReturn('http://test.com/test');

        $activity->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $activity->getTitle()
            ->shouldBeCalled()
            ->willReturn(str_repeat("T", 200));

        $activity->getMessage()
            ->shouldBeCalled()
            ->willReturn('http://test.com/test');

        $activity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entity = $activity;

        $builtNotification = $this->build($activity);

        $builtNotification->getTitle()->shouldBe('@test posted a link');
        $builtNotification->getBody()->shouldBe(str_repeat("T", 170).'...');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('');
    }

    public function it_should_trim_the_message_body_when_detecting_links(
        Activity $activity,
        User $user
    ) {
        $activity->getType()
            ->shouldBeCalled()
            ->willReturn('activity');

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn('test');
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('');

        $activity->getPermaURL()
            ->shouldBeCalled()
            ->willReturn('http://test.com/test');

        $activity->getOwnerEntity()
            ->shouldBeCalled()
            ->willReturn($user);

        $activity->getTitle()
            ->shouldBeCalled()
            ->willReturn(str_repeat("T", 200));

        $activity->getMessage()
            ->shouldBeCalled()
            ->willReturn(' ');

        $activity->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->entity = $activity;

        $builtNotification = $this->build($activity);

        $builtNotification->getTitle()->shouldBe('@test posted a link');
        $builtNotification->getBody()->shouldBe(str_repeat("T", 170).'...');
        $builtNotification->getUri()->shouldBe('newsfeed/123');
        $builtNotification->getMedia()->shouldBe('');
    }
}
