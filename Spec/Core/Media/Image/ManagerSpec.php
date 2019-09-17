<?php

namespace Spec\Minds\Core\Media\Image;

use Minds\Core\Media\Image\Manager;
use Minds\Core\Config;
use Minds\Core\Security\SignedUri;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\Video;
use Minds\Core\Comments\Comment;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $config;
    private $signedUri;

    public function let(Config $config, SignedUri $signedUri)
    {
        $this->beConstructedWith($config, $signedUri);
        $this->config = $config;
        $this->signedUri = $signedUri;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_public_asset_uri()
    {
        $activity = new Activity();
        $activity->set('entity_guid', 123);
        $this->config->get('cdn_url')
            ->willReturn('https://minds.dev/');
        $uri = 'https://minds.dev/fs/v1/thumbnail/123/xlarge';
        $this->signedUri->sign($uri, Argument::any())
            ->willReturn('signed url will be here');
        $this->getPublicAssetUri($activity)
            ->shouldBe('signed url will be here');
    }

    public function it_should_return_public_asset_uri_for_image()
    {
        $entity = new Image();
        $entity->set('guid', 123);
        $this->config->get('cdn_url')
            ->willReturn('https://minds.dev/');
        $uri = 'https://minds.dev/fs/v1/thumbnail/123/xlarge';
        $this->signedUri->sign($uri, Argument::any())
            ->willReturn('signed url will be here');
        $this->getPublicAssetUri($entity)
            ->shouldBe('signed url will be here');
    }

    public function it_should_return_public_asset_uri_for_video()
    {
        $entity = new Video();
        $entity->set('guid', 123);
        $this->config->get('cdn_url')
            ->willReturn('https://minds.dev/');
        $uri = 'https://minds.dev/fs/v1/thumbnail/123/xlarge';
        $this->signedUri->sign($uri, Argument::any())
            ->willReturn('signed url will be here');
        $this->getPublicAssetUri($entity)
            ->shouldBe('signed url will be here');
    }

    public function it_should_return_public_asset_uri_for_comment()
    {
        $entity = new Comment();
        $entity->setAttachment('attachment_guid', '123');
        $this->config->get('cdn_url')
            ->willReturn('https://minds.dev/');
        $uri = 'https://minds.dev/fs/v1/thumbnail/123/xlarge';
        $this->signedUri->sign($uri, Argument::any())
            ->willReturn('signed url will be here');
        $this->getPublicAssetUri($entity)
            ->shouldBe('signed url will be here');
    }
}
