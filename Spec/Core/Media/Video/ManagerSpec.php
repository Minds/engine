<?php

namespace Spec\Minds\Core\Media\Video;

use Aws\S3\S3Client;
use Minds\Common\Repository\Response;
use Minds\Core\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Media\Video\CloudflareStreams;
use Minds\Core\Media\Video\Manager;
use Minds\Core\Media\Video\Transcoder;
use Minds\Core\Storage\Quotas\Manager as StorageQuotasManager;
use Minds\Entities\Video;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;

class ManagerSpec extends ObjectBehavior
{
    private $config;
    private $s3;
    private $entitiesBuilder;
    private $transcoderManager;
    private $save;
    private $cloudflareStreamsManager;

    private Collaborator $objectStorageClientMock;
    private Collaborator $storageQuotasManagerMock;

    public function let(
        Config $config,
        S3Client $s3,
        EntitiesBuilder $entitiesBuilder,
        Transcoder\Manager $transcoderManager,
        Save $save,
        CloudflareStreams\Manager $cloudflareStreamsManager,
        ObjectStorageClient $objectStorageClientMock,
        StorageQuotasManager $storageQuotasManagerMock
    ) {
        $this->objectStorageClientMock = $objectStorageClientMock;
        $this->storageQuotasManagerMock = $storageQuotasManagerMock;

        $this->beConstructedWith(
            $config,
            $s3,
            $entitiesBuilder,
            $transcoderManager,
            $save,
            $cloudflareStreamsManager,
            $this->objectStorageClientMock,
            $this->storageQuotasManagerMock
        );
        $this->config = $config;
        $this->s3 = $s3;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->transcoderManager = $transcoderManager;
        $this->save = $save;
        $this->cloudflareStreamsManager = $cloudflareStreamsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_a_video()
    {
        $video = new Video();

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn($video);

        $this->get(123)
            ->shouldReturn($video);
    }

    public function it_should_return_available_sources()
    {
        $video = new Video();
        $video->set('guid', '123');

        $this->transcoderManager->getList([
            'guid' => '123',
            'legacyPolyfill' => true,
        ])
            ->shouldBeCalled()
            ->willReturn(new Response([
                (new Transcoder\Transcode())
                    ->setGuid('123')
                    ->setProfile(new Transcoder\TranscodeProfiles\X264_720p())
                    ->setStatus('created'),
                (new Transcoder\Transcode())
                    ->setGuid('123')
                    ->setProfile(new Transcoder\TranscodeProfiles\X264_360p())
                    ->setStatus('completed'),
                (new Transcoder\Transcode())
                    ->setGuid('123')
                    ->setProfile(new Transcoder\TranscodeProfiles\Webm_360p())
                    ->setStatus('completed'),
            ]));

        $sources = $this->getSources($video);
        $sources->shouldHaveCount(2);

        $sources[0]->getType()
            ->shouldBe('video/mp4');
        $sources[0]->getSize(360);
    
        $sources[1]->getType()
            ->shouldBe('video/webm');
        $sources[1]->getSize(360);
    }

    public function it_should_return_available_legacy_sources_while_skipping_720p()
    {
        $video = new Video();
        $video->set('guid', '123');

        $this->transcoderManager->getList([
            'guid' => '123',
            'legacyPolyfill' => true,
        ])
            ->shouldBeCalled()
            ->willReturn(new Response([
                (new Transcoder\Transcode())
                    ->setGuid('123')
                    ->setProfile(new Transcoder\TranscodeProfiles\X264_720p())
                    ->setStatus('completed'),
                (new Transcoder\Transcode())
                    ->setGuid('123')
                    ->setProfile(new Transcoder\TranscodeProfiles\X264_360p())
                    ->setStatus('completed'),
                (new Transcoder\Transcode())
                    ->setGuid('123')
                    ->setProfile(new Transcoder\TranscodeProfiles\Webm_360p())
                    ->setStatus('completed'),
            ]));

        $sources = $this->getSources($video);
        $sources->shouldHaveCount(2);

        $sources[0]->getType()
            ->shouldBe('video/mp4');
        $sources[0]->getSize(360);
    
        $sources[1]->getType()
            ->shouldBe('video/webm');
        $sources[1]->getSize(360);
    }

    public function it_should_get_a_signed_720p_video_url(RequestInterface $request, \Aws\CommandInterface $cmd)
    {
        $this->config->get('transcoder')
            ->willReturn([
                'dir' => 'dir',
            ]);
        $this->config->get('aws')
            ->willReturn([
                'region' => 'us-east-1',
                'useRoles' => true,
            ]);

        $this->config->get('cinemr_url')
            ->willReturn('https://url.com/cinemr');

        $this->s3->getCommand('GetObject', [
            'Bucket' => 'cinemr',
            'Key' => 'dir/123/720.mp4'
        ])
            ->shouldBeCalled()
            ->willReturn($cmd);

        $request->getUri()
            ->willReturn('s3-signed-url-here');

        $this->s3->createPresignedRequest(Argument::any(), Argument::any())
            ->willReturn($request);

        $video = new Video();
        $video->set('cinemr_guid', 123);
        $video->set('access_id', ACCESS_PRIVATE);
        $this->getPublicAssetUri($video, '720.mp4')
            ->shouldBe('s3-signed-url-here');
    }

    public function it_should_get_an_unsigned_720p_video_url(RequestInterface $request, \Aws\CommandInterface $cmd)
    {
        $this->config->get('transcoder')
            ->willReturn([
                'dir' => 'dir',
            ]);
        $this->config->get('aws')
            ->willReturn([
                'region' => 'us-east-1',
                'useRoles' => true,
            ]);

        $this->config->get('cinemr_url')
            ->willReturn('https://url.com/cinemr');

        $this->s3->getCommand('GetObject', [
            'Bucket' => 'cinemr',
            'Key' => 'dir/123/720.mp4'
        ])
            ->shouldBeCalled()
            ->willReturn($cmd);

        $request->getUri()
            ->willReturn('s3-signed-url-here');

        $this->s3->createPresignedRequest(Argument::any(), Argument::any())
            ->willReturn($request);

        $video = new Video();
        $video->set('cinemr_guid', 123);
        $this->getPublicAssetUri($video, '720.mp4')
            ->shouldBe('https://url.com/cinemr123/720.mp4');
    }

    public function it_should_create_transcoders_on_add()
    {
        $video = new Video();

        $this->save->setEntity($video)
            ->willReturn($this->save);
        $this->save->save()
            ->willReturn(true);

        $this->storageQuotasManagerMock->storeAssetQuota($video)
            ->shouldBeCalledOnce();

        $this->transcoderManager->createTranscodes($video)
            ->shouldBeCalled();

        $this->add($video);
    }
}
