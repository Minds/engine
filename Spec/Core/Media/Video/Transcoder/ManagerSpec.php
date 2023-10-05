<?php

namespace Spec\Minds\Core\Media\Video\Transcoder;

use Minds\Core\Media\Video\Transcoder\Manager;
use Minds\Core\Media\Video\Transcoder\Repository;
use Minds\Core\Media\Video\Transcoder\Delegates\QueueDelegate;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;
use Minds\Core\Media\Video\Transcoder\TranscodeStorage\TranscodeStorageInterface;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\Delegates\NotificationDelegate;
use Minds\Entities\Video;
use Minds\Entities\User;
use Minds\Common\Repository\Response;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $repository;
    private $queueDelegate;
    private $transcodeStorage;
    private $notificationDelegate;

    public function let(Repository $repository, QueueDelegate $queueDelegate, TranscodeStorageInterface $transcodeStorage, NotificationDelegate $notificationDelegate)
    {
        $this->beConstructedWith($repository, $queueDelegate, $transcodeStorage, $notificationDelegate);
        $this->repository = $repository;
        $this->queueDelegate = $queueDelegate;
        $this->transcodeStorage = $transcodeStorage;
        $this->notificationDelegate = $notificationDelegate;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_upload_source_file_to_transcoder_storage()
    {
        $video = new Video();
        $video->guid = 123;

        $this->transcodeStorage->add(Argument::that(function ($transcode) use ($video) {
            return $transcode->getProfile() instanceof TranscodeProfiles\Source
                && $transcode->getVideo()->getGuid() === $video->getGuid();
        }), '/tmp/my-fake-video')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->uploadSource($video, '/tmp/my-fake-video')
            ->shouldReturn(true);
    }

    public function it_should_return_a_signed_url_for_client_upload()
    {
        $this->transcodeStorage->getClientSideUploadUrl(Argument::type(Transcode::class))
            ->shouldBeCalled()
            ->willReturn('signed-url-here');
        $this->getClientSideUploadUrl(new Video())
            ->shouldBe('signed-url-here');
    }

    public function it_should_create_transcodes_from_video()
    {
        $video = new Video();
        $video->guid = 123;

        $user = new User();
        $user->pro = 0;
        $video->set('owner', $user);

        foreach ([
            TranscodeProfiles\Thumbnails::class,
            TranscodeProfiles\X264_360p::class,
            TranscodeProfiles\X264_720p::class,
            TranscodeProfiles\X264_1080p::class,
            TranscodeProfiles\Webm_360p::class,
            TranscodeProfiles\Webm_720p::class,
            TranscodeProfiles\Webm_1080p::class,
        ] as $i => $profile) {
            // Should be added to repo
            $this->repository->add(Argument::that(function ($transcode) use ($video, $profile) {
                return $transcode->getProfile() instanceof $profile
                    && $transcode->getGuid() === $video->getGuid();
            }))
                ->shouldBeCalled()
                ->willReturn(true);
            // And queue
            $this->queueDelegate->onAdd(Argument::that(function ($transcode) use ($video, $profile) {
                return $transcode->getProfile() instanceof $profile
                    && $transcode->getGuid() === $video->getGuid();
            }))
                ->shouldBeCalled();
        }

        $this->createTranscodes($video);
    }

    public function it_should_add_transcode()
    {
        $transcode = new Transcode();
        $this->repository->add($transcode)
            ->shouldBeCalled();
        $this->queueDelegate->onAdd($transcode)
            ->shouldBeCalled();
        $this->add($transcode);
    }
    
    public function it_should_update(Transcode $transcode, Video $video)
    {
        $transcode->getProfile()
            ->willReturn(new TranscodeProfiles\X264_360p());
        $transcode->getVideo()
            ->willReturn($video);
        $transcode->getStatus()
            ->willReturn('transcoding');

        $this->repository->update($transcode, [ 'status' ])
            ->shouldBeCalled();

        $this->update($transcode, [ 'status' ]);
    }

    public function it_should_get_legacy_files()
    {
        $this->repository->getList([
            'guid' => '123',
            'profileId' => null,
            'status' => null,
            'legacyPolyfill' => true,
        ])
            ->shouldBeCalled()
            ->willReturn(new Response());

        $this->transcodeStorage->ls('123')
            ->shouldBeCalled()
            ->willReturn([
                '/my-dir/123/360.mp4',
                '/my-dir/123/720.mp4',
                '/my-dir/123/360.webm',
            ]);

        $transcodes = $this->getList([
            'guid' => '123',
            'legacyPolyfill' => true,
        ]);
        $transcodes->shouldHaveCount(3);
        $transcodes[0]->getProfile()
            ->getStorageName('360.mp4');
    }
}
