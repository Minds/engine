<?php

namespace Spec\Minds\Core\Media\Audio;

use DateTimeImmutable;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\FFProbe\DataMapping\Format;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Media\AdvancedMedia;
use FFMpeg\Media\Audio;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\GuidBuilder;
use Minds\Core\Media\Audio\AudioAssetStorageService;
use Minds\Core\Media\Audio\AudioEntity;
use Minds\Core\Media\Audio\AudioRepository;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Media\Audio\AudioThumbnailService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\SimpleCache\CacheInterface;

class AudioServiceSpec extends ObjectBehavior
{
    private Collaborator $audioAssetStorageServiceMock;
    private Collaborator $audioRepositoryMock;
    private Collaborator $audioThumbnailServiceMock;
    private Collaborator $fFMpegMock;
    private Collaborator $fFProbeMock;
    private Collaborator $actionEventsTopicMock;
    private Collaborator $cacheMock;
    private Collaborator $guidMock;

    public function let(
        AudioAssetStorageService $audioAssetStorageServiceMock,
        AudioRepository $audioRepositoryMock,
        AudioThumbnailService $audioThumbnailServiceMock,
        FFMpeg $fFMpegMock,
        FFProbe $fFProbeMock,
        ActionEventsTopic $actionEventsTopicMock,
        CacheInterface $cacheMock,
        GuidBuilder $guidMock
    ) {
        $this->beConstructedWith($audioAssetStorageServiceMock, $audioRepositoryMock, $audioThumbnailServiceMock, $fFMpegMock, $fFProbeMock, $actionEventsTopicMock, $cacheMock, $guidMock);
        $this->audioAssetStorageServiceMock = $audioAssetStorageServiceMock;
        $this->audioRepositoryMock = $audioRepositoryMock;
        $this->audioThumbnailServiceMock = $audioThumbnailServiceMock;
        $this->fFMpegMock = $fFMpegMock;
        $this->fFProbeMock = $fFProbeMock;
        $this->actionEventsTopicMock = $actionEventsTopicMock;
        $this->cacheMock = $cacheMock;
        $this->guidMock = $guidMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AudioService::class);
    }

    public function it_should_return_entity_from_database()
    {
        $this->cacheMock->has('audio:entity:123')
            ->shouldBeCalled()
            ->willReturn(false);

        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->audioRepositoryMock->getByGuid(123)
            ->willReturn($audioEntity);

        $this->cacheMock->set('audio:entity:123', serialize($audioEntity))
            ->shouldBeCalled()
            ->willReturn(true);
    
        $this->getByGuid(123)->shouldBe($audioEntity);
    }

    public function it_should_return_entity_from_cache()
    {
        $this->cacheMock->has('audio:entity:123')
            ->shouldBeCalled()
            ->willReturn(true);

        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->cacheMock->get('audio:entity:123')
            ->shouldBeCalled()
            ->willReturn(serialize($audioEntity));
    
        $this->audioRepositoryMock->getByGuid(123)
            ->shouldNotBeCalled();

        $this->cacheMock->set('audio:entity:123', serialize($audioEntity))
            ->shouldNotBeCalled();
    
        $response = $this->getByGuid(123);
        $response->shouldBeAnInstanceOf(AudioEntity::class);
        $response->guid->shouldBe(123);
        $response->ownerGuid->shouldBe(456);
    }

    public function it_should_get_download_url()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->cacheMock->has('audio:download:123')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->audioAssetStorageServiceMock->getDownloadUrl($audioEntity, 'specfile.mp3')
            ->shouldBeCalled()
            ->willReturn('my-url');

        $this->cacheMock->set('audio:download:123', 'my-url', Argument::type('integer'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->getDownloadUrl($audioEntity, 'specfile.mp3')
            ->shouldBe('my-url');
    }

    public function it_should_get_download_url_from_cache()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->cacheMock->has('audio:download:123')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cacheMock->get('audio:download:123')
            ->shouldBeCalled()
            ->willReturn('my-url');

        $this->audioAssetStorageServiceMock->getDownloadUrl($audioEntity, 'specfile.mp3')
            ->shouldNotBeCalled();

        $this->cacheMock->set('audio:download:123', 'my-url', Argument::type('integer'))
            ->shouldNotBeCalled();

        $this->getDownloadUrl($audioEntity, 'specfile.mp3')
            ->shouldBe('my-url');
    }

    public function it_should_return_a_client_side_upload_url()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->audioAssetStorageServiceMock->getClientSideUploadUrl($audioEntity)
            ->shouldBeCalled()
            ->willReturn('https://my-url.com/123');

        $this->getClientSideUploadUrl($audioEntity)->shouldBe('https://my-url.com/123');
    }

    public function it_should_save_audio_entity_to_repository()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->audioRepositoryMock->add($audioEntity)
            ->shouldBeCalled();

        $this->onUploadInitiated($audioEntity);
    }

    public function it_should_queue_event_on_completed_audio()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );
        $user = new User();

        $this->audioRepositoryMock->update($audioEntity, ['uploadedAt'])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cacheMock->delete('audio:entity:123')
            ->shouldBeCalled();

        $this->actionEventsTopicMock->send(Argument::type(ActionEvent::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onUploadCompleted($audioEntity, $user);
    }

    public function it_should_queue_event_on_completed_audio_if_already_marked_as_uploaded()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
            uploadedAt: new DateTimeImmutable(),
        );
        $user = new User();

        $this->audioRepositoryMock->update($audioEntity, ['uploadedAt'])
            ->shouldNotBeCalled();

        $this->cacheMock->delete('audio:entity:123')
            ->shouldNotBeCalled();

        $this->actionEventsTopicMock->send(Argument::type(ActionEvent::class))
            ->shouldNotBeCalled();

        $this->shouldThrow(ForbiddenException::class)->duringOnUploadCompleted($audioEntity, $user);
    }

    public function it_should_process_audio(AdvancedMedia $ffmpegAMMock, Format $ffprobeFormatMock)
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
            uploadedAt: new DateTimeImmutable(),
        );

        $resource = fopen('php://temp', 'r+');
        $this->audioAssetStorageServiceMock->downloadToTmpfile($audioEntity)
            ->shouldBeCalled()
            ->willReturn($resource);

        $this->fFMpegMock->openAdvanced(Argument::any())->willReturn($ffmpegAMMock);
        $ffmpegAMMock->map(['0:a'], Argument::type(Mp3::class), Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($ffmpegAMMock);
        $ffmpegAMMock->save()->shouldBeCalled();

        $this->fFProbeMock->format(Argument::type('string'))->willReturn($ffprobeFormatMock);
        $ffprobeFormatMock->get('duration')->willReturn(12.5);

        $this->audioAssetStorageServiceMock->upload(Argument::that(
            fn (AudioEntity $input) =>
            $input->durationSecs === 12.5
        ), sys_get_temp_dir() . "/123.mp3")
            ->shouldBeCalled();

        $this->audioRepositoryMock->update(Argument::that(
            fn (AudioEntity $input) =>
            $input->processedAt instanceof DateTimeImmutable
            && $input->durationSecs === 12.5
        ), ['processedAt', 'durationSecs'])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cacheMock->delete('audio:entity:123')
            ->shouldBeCalled();

        $this->processAudio($audioEntity)
            ->shouldBe(true);
    }

    public function it_should_update_the_access_id_to_that_of_parent_activity_when_posted()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
            uploadedAt: new DateTimeImmutable(),
        );

        $this->audioRepositoryMock->updateAccessId(Argument::that(
            fn (AudioEntity $input) =>
            $input->accessId === 789
        ))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cacheMock->delete('audio:entity:123')
            ->shouldBeCalled();

        $this->onActivityPostCreated($audioEntity, 789)
            ->shouldBe(true);
    }

    public function it_should_not_update_access_id_if_already_not_private()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
            accessId: 789,
            uploadedAt: new DateTimeImmutable(),
        );

        $this->audioRepositoryMock->updateAccessId(Argument::any())
            ->shouldNotBeCalled();
        
        $this->cacheMock->delete('audio:entity:123')
            ->shouldNotBeCalled();

        $this->shouldThrow(ForbiddenException::class)->duringOnActivityPostCreated($audioEntity, 789);
    }

    public function it_should_upload_thumbnail_from_blob()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
        );

        $this->audioThumbnailServiceMock->process($audioEntity, 'image-blob')
            ->shouldBeCalled();

        $this->uploadThumbnailFromBlob($audioEntity, 'image-blob');
    }

    public function it_should_create_an_audio_entity_from_remote_file_url(
        User $userMock
    ) {
        $url = 'https://example.minds.com/123.mp3';
        $ownerGuid = 456;

        $this->guidMock->build()->willReturn(123);
        $userMock->getGuid()->willReturn($ownerGuid);

        $this->audioRepositoryMock->add(Argument::type(AudioEntity::class))
            ->shouldBeCalled();

        $this->audioRepositoryMock->update(Argument::type(AudioEntity::class), ['uploadedAt'])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cacheMock->delete('audio:entity:123')
            ->shouldBeCalled();

        $this->actionEventsTopicMock->send(Argument::type(ActionEvent::class))
            ->shouldBeCalled()
            ->willReturn(true);


        $this->onRemoteFileUrlProvided($userMock, $url)->shouldBeAnInstanceOf(AudioEntity::class);
    }
}
