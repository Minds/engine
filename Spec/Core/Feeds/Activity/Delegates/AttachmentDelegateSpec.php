<?php

namespace Spec\Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Config;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Activity\Delegates\AttachmentDelegate;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AttachmentDelegateSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Save */
    protected $saveAction;

    /** @var Delete */
    protected $deleteAction;

    public function let(
        Config $config,
        EntitiesBuilder $entitiesBuilder,
        Save $saveAction,
        Delete $deleteAction
    ) {
        $this->config = $config;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->saveAction = $saveAction;
        $this->deleteAction = $deleteAction;
        $this->beConstructedWith($config, $entitiesBuilder, $saveAction, $deleteAction);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AttachmentDelegate::class);
    }

    public function it_should_attach_a_video(User $actor, Activity $activity, Video $video)
    {
        $this->entitiesBuilder->single(5000)
            ->shouldBeCalled()
            ->willReturn($video);

        $actor->isAdmin()
            ->shouldBeCalled()
            ->willReturn(false);

        $video->get('owner_guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $actor->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $activity->get('title')
            ->shouldBeCalled()
            ->willReturn('phpspec');

        $video->set('title', 'phpspec')
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->getMessage()
            ->shouldBeCalled()
            ->willReturn('phpspec test');

        $video->setDescription('phpspec test')
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->getContainerGUID()
            ->shouldBeCalled()
            ->willReturn(888);

        $video->set('container_guid', 888)
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->getAccessID()
            ->shouldBeCalled()
            ->willReturn(999);

        $video->set('access_id', 999)
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->get('license')
            ->shouldBeCalled()
            ->willReturn('cc');

        $video->set('license', 'cc')
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->getMature()
            ->shouldBeCalled()
            ->willReturn(false);

        $video->setFlag('mature', false)
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->isPayWall()
            ->shouldBeCalled()
            ->willReturn(true);

        $video->set('access_id', 0)
            ->shouldBeCalled()
            ->willReturn($video);

        $video->set('hidden', true)
            ->shouldBeCalled()
            ->willReturn($video);

        $video->setPayWall(true)
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->getWireThreshold()
            ->shouldBeCalled()
            ->willReturn(['wire' => 1]);

        $video->setWireThreshold(['wire' => 1])
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->getNsfw()
            ->shouldBeCalled()
            ->willReturn([]);

        $video->setNsfw([])
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(998877);

        $video->set('time_created', 998877)
            ->shouldBeCalled()
            ->willReturn($video);

        $activity->getTags()
            ->shouldBeCalled()
            ->willReturn(['tag1', 'tag2']);

        $video->setTags(['tag1', 'tag2'])
            ->shouldBeCalled()
            ->willReturn($video);

        $video->get('subtype')
            ->shouldBeCalled()
            ->willReturn('video');

        $activity->setFromEntity($video)
            ->shouldBeCalled()
            ->willReturn($activity);

        $video->getIconUrl()
            ->shouldBeCalled()
            ->willReturn('phpspec://thumb');

        $video->get('guid')
            ->shouldBeCalled()
            ->willReturn(5000);

        $video->get('width')
            ->shouldBeCalled()
            ->willReturn(null);

        $video->get('height')
            ->shouldBeCalled()
            ->willReturn(null);

        $video->getFlag('mature')
            ->shouldBeCalled()
            ->willReturn(false);

        $activity->setCustom('video', [
            'thumbnail_src' => 'phpspec://thumb',
            'guid' => 5000,
            'mature' => false,
            'width' => null,
            'height' => null
        ])
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getPending()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->saveAction->setEntity($video)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setActor($actor)
            ->onCreate($activity, 5000)
            ->shouldReturn($activity);
    }


    public function it_should_attach_an_image(User $actor, Activity $activity, Image $image)
    {
        $this->entitiesBuilder->single(5000)
            ->shouldBeCalled()
            ->willReturn($image);

        $actor->isAdmin()
            ->shouldBeCalled()
            ->willReturn(true);

        $image->get('owner_guid')
            ->willReturn(1000);

        $actor->get('guid')
            ->willReturn(1010);

        $activity->get('title')
            ->shouldBeCalled()
            ->willReturn('phpspec');

        $image->set('title', 'phpspec')
            ->shouldBeCalled()
            ->willReturn($image);

        $activity->getMessage()
            ->shouldBeCalled()
            ->willReturn('phpspec test');

        $image->setDescription('phpspec test')
            ->shouldBeCalled()
            ->willReturn($image);

        $activity->getContainerGUID()
            ->shouldBeCalled()
            ->willReturn(888);

        $image->set('container_guid', 888)
            ->shouldBeCalled()
            ->willReturn($image);

        $activity->getAccessID()
            ->shouldBeCalled()
            ->willReturn(999);

        $image->set('access_id', 999)
            ->shouldBeCalled()
            ->willReturn($image);

        $activity->get('license')
            ->shouldBeCalled()
            ->willReturn('');

        $image->set('license', Argument::cetera())
            ->shouldNotBeCalled();

        $activity->getMature()
            ->shouldBeCalled()
            ->willReturn(true);

        $image->setFlag('mature', true)
            ->shouldBeCalled()
            ->willReturn($image);

        $activity->isPayWall()
            ->shouldBeCalled()
            ->willReturn(false);

        $image->set('access_id', 0)
            ->shouldNotBeCalled();

        $image->set('hidden', true)
            ->shouldNotBeCalled();

        $image->setFlag('paywall', true)
            ->shouldNotBeCalled();

        $image->setWireThreshold()
            ->shouldNotBeCalled();

        $activity->getNsfw()
            ->shouldBeCalled()
            ->willReturn([1, 2]);

        $image->setNsfw([1, 2])
            ->shouldBeCalled()
            ->willReturn($image);

        $activity->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(998877);

        $image->set('time_created', 998877)
            ->shouldBeCalled()
            ->willReturn($image);

        $activity->getTags()
            ->shouldBeCalled()
            ->willReturn(['tag1', 'tag2']);

        $image->setTags(['tag1', 'tag2'])
            ->shouldBeCalled()
            ->willReturn($image);

        $image->get('subtype')
            ->shouldBeCalled()
            ->willReturn('image');

        $activity->setFromEntity($image)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->config->get('cdn_url')
            ->shouldBeCalled()
            ->willReturn('cdn.phpspec/');

        $image->get('guid')
            ->shouldBeCalled()
            ->willReturn(5000);

        $this->config->get('site_url')
            ->shouldBeCalled()
            ->willReturn('phpspec.test/');

        $image->get('container_guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $image->getFlag('mature')
            ->shouldBeCalled()
            ->willReturn(true);

        $image->get('width')
            ->shouldBeCalled()
            ->willReturn(320);

        $image->get('height')
            ->shouldBeCalled()
            ->willReturn(240);

        $image->get('gif')
            ->shouldBeCalled()
            ->willReturn(true);

        $image->get('blurhash')
            ->shouldBeCalled()
            ->willReturn('something');

        $activity->setCustom('batch', [[
            'src' => 'cdn.phpspec/fs/v1/thumbnail/5000',
            'href' => 'phpspec.test/media/1000/5000',
            'mature' => true,
            'width' => 320,
            'height' => 240,
            'blurhash' => 'something',
            'gif' => true,
        ]])
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getPending()
            ->shouldBeCalled()
            ->willReturn(true);

        $image->set('access_id', 0)
            ->shouldBeCalled()
            ->willReturn($image);

        $this->saveAction->setEntity($image)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setActor($actor)
            ->onCreate($activity, 5000)
            ->shouldReturn($activity);
    }

    public function it_should_delete_attachment_and_detach_the_entity(Activity $activity, Video $video)
    {
        $activity->getOwnerGUID()
            ->shouldBeCalled()
            ->willReturn(1000);

        $activity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $activity->get('custom_type')
            ->shouldBeCalled()
            ->willReturn('video');

        $this->entitiesBuilder->single(5000)
            ->shouldBeCalled()
            ->willReturn($video);

        $video->get('owner_guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->deleteAction->setEntity($video)
            ->shouldBeCalled()
            ->willReturn($this->deleteAction);

        $this->deleteAction->delete()
            ->shouldBeCalled()
            ->willReturn(true);

        $activity->setEntityGuid(null)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->setCustom(null, null)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this
            ->onDelete($activity)
            ->shouldReturn($activity);
    }
}
