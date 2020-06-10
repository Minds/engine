<?php

namespace Spec\Minds\Core\Notification\Extensions;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\I18n\Translator;
use Minds\Core\Notification\Extensions\Push;
use Minds\Core\Notification\Notification as NotificationEntity;
use Minds\Entities\Entity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PushSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    protected $translator;

    public function let($config, Translator $translator, EntitiesBuilder $builder, User $user)
    {
        $this->config = $config;
        $this->translator = $translator;

        Di::_()->bind('I18n\Translator', function () use ($translator) {
            return $translator->getWrappedObject();
        });

        Di::_()->bind('EntitiesBuilder', function () use ($builder) {
            return $builder->getWrappedObject();
        });

        $builder->single(Argument::any())
            ->willReturn($user);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Push::class);
    }

    public function it_should_construct_commented_on_a_post_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.user.a.post', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on a post');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '1',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on a post');
    }

    public function it_should_construct_commented_on_a_image_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'image';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.user.image', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on a image');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '1',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on a image');
    }

    public function it_should_construct_commented_on_a_video_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'video';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.user.video', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on a video');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '1',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on a video');
    }

    public function it_should_construct_commented_on_a_blog_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'blog';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.user.blog', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on a blog');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '1',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on a blog');
    }

    public function it_should_construct_commented_on_your_post_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->ownerObj = null;

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.your.post', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on your post');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on your post');
    }

    public function it_should_construct_commented_on_your_activity_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->ownerObj = null;
        $entity->type = 'activity';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.your.activity', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on your activity');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on your activity');
    }

    public function it_should_construct_commented_on_your_image_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->ownerObj = null;
        $entity->type = 'object';
        $entity->subtype = 'image';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.your.image', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on your image');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on your image');
    }

    public function it_should_construct_commented_on_your_video_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->ownerObj = null;
        $entity->type = 'object';
        $entity->subtype = 'video';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.your.video', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on your video');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on your video');
    }

    public function it_should_construct_commented_on_your_blog_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->ownerObj = null;
        $entity->type = 'object';
        $entity->subtype = 'blog';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.your.blog', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on your blog');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on your blog');
    }

    public function it_should_construct_commented_on_alices_post_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->ownerObj = ['name' => 'Alice'];

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('comment.user.post', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob commented on Alice\'s post');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'comment'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob commented on Alice\'s post');
    }


    public function it_should_construct_your_liked_your_comment_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'comment';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('like.comment', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob voted up your comment');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'like'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob voted up your comment');
    }

    public function it_should_construct_your_liked_your_activity_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'activity';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('like.activity', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob voted up your activity');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'like'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob voted up your activity');
    }

    public function it_should_construct_your_liked_your_object_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'message';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('like.message', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob voted up your message');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'like'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob voted up your message');
    }

    public function it_should_construct_your_liked_your_title_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->title = 'Title';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('like.title', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob voted up Title');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'like'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob voted up Title');
    }

    public function it_should_construct_your_liked_your_image(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'image';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('like.image', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob voted up your image');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'like'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob voted up your image');
    }

    public function it_should_construct_your_liked_your_video(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'video';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('like.video', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob voted up your video');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'like'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob voted up your video');
    }

    public function it_should_construct_your_liked_your_blog(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'blog';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('like.blog', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob voted up your blog');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'like'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob voted up your blog');
    }

    public function it_should_construct_your_tagged_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('tag.post', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob mentioned you in a post');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'tag'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob mentioned you in a post');
    }

    public function it_should_construct_your_tagged_comment_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'comment';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('tag.comment', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob mentioned you in a comment');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'tag'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob mentioned you in a comment');
    }

    public function it_should_construct_your_subscribed_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'blog';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('user.subscribed', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob mentioned you in a post');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'friends'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob mentioned you in a post');
    }

    public function it_should_construct_your_reminded_activity(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'activity';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('remind.activity', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob reminded your activity');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'remind'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob reminded your activity');
    }

    public function it_should_construct_your_reminded_image(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'image';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('remind.image', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob reminded your image');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'remind'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob reminded your image');
    }

    public function it_should_construct_your_reminded_video(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'video';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('remind.video', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob reminded your video');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'remind'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob reminded your video');
    }

    public function it_should_construct_your_reminded_blog(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';
        $entity->type = 'object';
        $entity->subtype = 'blog';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('remind.blog', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob reminded your blog');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'remind'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob reminded your blog');
    }

    public function it_should_construct_your_reminded_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['test' => 123]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('remind.post', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob reminded your post');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'remind'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob reminded your post');
    }

    public function it_should_construct_your_boost_gift_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['impressions' => 99]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('boost.gift', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob gifted you 99 views');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'boost_gift'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob gifted you 99 views');
    }

    public function it_should_construct_your_boost_request_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['points' => 99]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('boost.request', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob has requested a boost of 99 points');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'boost_request'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob has requested a boost of 99 points');
    }

    public function it_should_construct_your_boost_accepted_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['impressions' => 99]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('boost.accepted', Argument::any())
            ->shouldBeCalled()
            ->willReturn('99 views for post were accepted');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'boost_accepted'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('99 views for post were accepted'); //????
    }

    public function it_should_construct_your_boost_rejected_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['impressions' => 99]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('boost.rejected', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Your boost request for post was rejected');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'boost_rejected'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Your boost request for post was rejected');
    }

    public function it_should_construct_your_boost_revoked_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['impressions' => 99]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('boost.revoked', Argument::any())
            ->shouldBeCalled()
            ->willReturn('You revoked the boost request for post');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'boost_revoked'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('You revoked the boost request for post');
    }

    public function it_should_construct_your_boost_completed_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['impressions' => 99]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');
        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('boost.completed', Argument::any())
            ->shouldBeCalled()
            ->willReturn('99/99 impressions were met for post');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'boost_completed'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('99/99 impressions were met for post');
    }

    public function it_should_construct_your_group_invite_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['group' => ['name' => 'test_group']]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');

        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('group.invite', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Bob invited you to test_group');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'group_invite'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Bob invited you to test_group');
    }

    public function it_should_construct_your_messenger_invite_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $notification->getData()
            ->shouldBeCalled()
            ->willReturn(['group' => ['name' => 'test_group']]);
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');
        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('messenger.invite', Argument::any())
            ->shouldBeCalled()
            ->willReturn('@Bob wants to chat with you!');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'messenger_invite'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('@Bob wants to chat with you!');
    }

    public function it_should_construct_your_referral_ping_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');
        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('referral.ping', Argument::any())
            ->shouldBeCalled()
            ->willReturn('Free tokens are waiting for you! Once you join the rewards program by setting up your Minds wallet, both you and @Bob will earn tokens for your referral');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'referral_ping'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('Free tokens are waiting for you! Once you join the rewards program by setting up your Minds wallet, both you and @Bob will earn tokens for your referral');
    }

    public function it_should_construct_your_referral_pending_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');
        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('referral.pending', Argument::any())
            ->shouldBeCalled()
            ->willReturn('You have a pending referral! @Bob used your referral link when they signed up for Minds. You\'ll get tokens once they join the rewards program and set up their wallet');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'referral_pending'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('You have a pending referral! @Bob used your referral link when they signed up for Minds. You\'ll get tokens once they join the rewards program and set up their wallet');
    }

    public function it_should_construct_your_referral_complete_message(NotificationEntity $notification, User $user, Entity $entity)
    {
        $user = new User();
        $user->name = 'Bob';
        $user->setLanguage('en');
        $entity = new Entity();
        $entity->owner_guid = '123';

        $this->translator->setLocale(Argument::any())
            ->shouldBeCalled();

        $this->translator->trans('referral.complete', Argument::any())
            ->shouldBeCalled()
            ->willReturn('You\'ve earned tokens for the completed referral of @Bob');

        $this->buildNotificationMessage([
            'notification' => $notification,
            'params' => ['notification_view' => 'referral_complete'],
            'to' => '123',
            'toObj' => $user,
        ], $user, $entity)->shouldBe('You\'ve earned tokens for the completed referral of @Bob');
    }

    public function getMatchers(): array
    {
        $matchers['beAnArrayOf'] = function ($subject, $count, $class) {
            if (!is_array($subject) || ($count !== null && count($subject) !== $count)) {
                throw new FailureException("Subject should be an array of $count elements");
            }

            $validTypes = true;

            foreach ($subject as $element) {
                if (!($element instanceof $class)) {
                    $validTypes = false;
                    break;
                }
            }

            if (!$validTypes) {
                throw new FailureException("Subject should be an array of {$class}");
            }

            return true;
        };

        return $matchers;
    }
}
