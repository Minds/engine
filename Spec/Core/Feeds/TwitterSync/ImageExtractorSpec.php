<?php

namespace Spec\Minds\Core\Feeds\TwitterSync;

use GuzzleHttp\Client;
use Minds\Core\Feeds\Activity\Delegates\AttachmentDelegate;
use Minds\Core\Feeds\TwitterSync\ConnectedAccount;
use Minds\Core\Feeds\TwitterSync\ImageExtractor;
use Minds\Core\Feeds\TwitterSync\TwitterUser;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Core\Entities\Actions\Save;
use Minds\Entities\Image;

class ImageExtractorSpec extends ObjectBehavior
{
    protected $httpClient;
    protected $logger;
    protected $attachmentDelegate;
    protected $saveAction;

    public function let(
        Client $httpClient,
        Logger $logger,
        AttachmentDelegate $attachmentDelegate,
        Save $saveAction,
    ) {
        $this->beConstructedWith($httpClient, $logger, $attachmentDelegate, $saveAction);
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->attachmentDelegate = $attachmentDelegate;
        $this->saveAction = $saveAction;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ImageExtractor::class);
    }

    public function it_should_extract_and_upload_and_save_an_image(Activity $activity, User $user)
    {
        $user->guid = 123;
        $activity->getOwnerEntity()->shouldBeCalled()->willReturn($user);

        $imageUrl = 'https://pbs.twimg.com/media/abc123photo.jpg';

        $this->httpClient->request('GET', $imageUrl, [
            'stream' => true
        ])
            ->shouldBeCalled()
            ->willReturn(new JsonResponse('123456789'));
        $this->saveAction->setEntity(Argument::that(function ($entity) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->save(true)
            ->shouldBeCalled()
            ->willReturn('123');

        $this->extractAndUploadToActivity($imageUrl, $activity)->shouldReturn($activity);
    }
}
