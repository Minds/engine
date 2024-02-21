<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Notifications\Push\ManualSend\Services;

use GuzzleHttp\Stream\StreamInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications\Push\ManualSend\Enums\PushNotificationPlatformEnum;
use Minds\Core\Notifications\Push\ManualSend\Models\ManualSendRequest;
use Minds\Core\Notifications\Push\ManualSend\Services\ManualSendService;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ResponseInterface;

class ManualSendServiceSpec extends ObjectBehavior
{
    public Collaborator $fcmService;
    public Collaborator $apnsService;
    public Collaborator $logger;

    public function let(
        FcmService $fcmService,
        ApnsService $apnsService,
        Logger $logger
    ) {
        $this->fcmService = $fcmService;
        $this->apnsService = $apnsService;
        $this->logger = $logger;
        
        $this->beConstructedWith(
            $fcmService,
            $apnsService,
            $logger
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ManualSendService::class);
    }

    // Android

    public function it_should_handle_sending_to_android(
        ResponseInterface $response,
        StreamInterface $streamInterface
    ): void {
        $platform = PushNotificationPlatformEnum::ANDROID;
        $token = 'token';
        $title = 'title';
        $body = 'body';
        $uri = 'https://example.minds.com/';
        $iconUrl = 'https://example.minds.com/img1.png';
        $mediaUrl = 'https://example.minds.com/img2.png';

        $manualSendRequest = new ManualSendRequest(
            platform: $platform,
            token: $token,
            title: $title,
            body: $body,
            uri: $uri,
            iconUrl: $iconUrl,
            mediaUrl: $mediaUrl
        );

        $streamInterface->getContents()
            ->shouldBeCalled()
            ->willReturn(json_encode(['status' => 200]));

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($streamInterface);

        $this->fcmService->request([
            'message' => [
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'uri' => $uri,
                    'largeIcon' => $iconUrl,
                    'bigPicture' => $mediaUrl
                ],
                'token' => $token,
          ],
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->send($manualSendRequest)->shouldBe(true);
    }

    public function it_should_handle_errors_when_sending_to_android(
        ResponseInterface $response,
        StreamInterface $streamInterface
    ): void {
        $platform = PushNotificationPlatformEnum::ANDROID;
        $token = 'token';
        $title = 'title';
        $body = 'body';
        $uri = 'https://example.minds.com/';
        $iconUrl = 'https://example.minds.com/img1.png';
        $mediaUrl = 'https://example.minds.com/img2.png';
        $errorMessage = 'ERROR';

        $manualSendRequest = new ManualSendRequest(
            platform: $platform,
            token: $token,
            title: $title,
            body: $body,
            uri: $uri,
            iconUrl: $iconUrl,
            mediaUrl: $mediaUrl
        );

        $streamInterface->getContents()
            ->shouldBeCalled()
            ->willReturn(json_encode(['error' => $errorMessage]));

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($streamInterface);

        $this->fcmService->request([
            'message' => [
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'uri' => $uri,
                    'largeIcon' => $iconUrl,
                    'bigPicture' => $mediaUrl
                ],
                'token' => $token,
          ],
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->logger->error($errorMessage)->shouldBeCalled();

        $this->shouldThrow(new ServerErrorException('An unexpected error has occurred'))->duringSend($manualSendRequest);
    }

    public function it_should_handle_empty_response_when_sending_to_android(
        ResponseInterface $response,
        StreamInterface $streamInterface
    ): void {
        $platform = PushNotificationPlatformEnum::ANDROID;
        $token = 'token';
        $title = 'title';
        $body = 'body';
        $uri = 'https://example.minds.com/';
        $iconUrl = 'https://example.minds.com/img1.png';
        $mediaUrl = 'https://example.minds.com/img2.png';

        $manualSendRequest = new ManualSendRequest(
            platform: $platform,
            token: $token,
            title: $title,
            body: $body,
            uri: $uri,
            iconUrl: $iconUrl,
            mediaUrl: $mediaUrl
        );

        $streamInterface->getContents()
            ->shouldBeCalled()
            ->willReturn(json_encode([]));

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($streamInterface);

        $this->fcmService->request([
            'message' => [
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'uri' => $uri,
                    'largeIcon' => $iconUrl,
                    'bigPicture' => $mediaUrl
                ],
                'token' => $token,
          ],
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->logger->error('An unexpected error has occurred')->shouldBeCalled();

        $this->shouldThrow(new ServerErrorException('An unexpected error has occurred'))->duringSend($manualSendRequest);
    }

    // iOS

    public function it_should_handle_sending_to_ios(
        ResponseInterface $response,
        StreamInterface $streamInterface
    ): void {
        $platform = PushNotificationPlatformEnum::IOS;
        $token = 'token';
        $title = 'title';
        $body = 'body';
        $uri = 'https://example.minds.com/';
        $iconUrl = 'https://example.minds.com/img1.png';
        $mediaUrl = 'https://example.minds.com/img2.png';

        $manualSendRequest = new ManualSendRequest(
            platform: $platform,
            token: $token,
            title: $title,
            body: $body,
            uri: $uri,
            iconUrl: $iconUrl,
            mediaUrl: $mediaUrl
        );

        $streamInterface->getContents()
            ->shouldBeCalled()
            ->willReturn(json_encode(['status' => 200]));

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($streamInterface);
            
        $this->apnsService->request($token, [], [
            'aps' => [
                "mutable-content" => 1,
                'alert' => [
                    'title' => $title,
                    'body' => $body
                ],
                'badge' => '',
            ],
            'uri' => $uri,
            'largeIcon' => $iconUrl
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->send($manualSendRequest)->shouldBe(true);
    }

    public function it_should_handle_errors_when_sending_to_ios(
        ResponseInterface $response,
        StreamInterface $streamInterface
    ): void {
        $platform = PushNotificationPlatformEnum::IOS;
        $token = 'token';
        $title = 'title';
        $body = 'body';
        $uri = 'https://example.minds.com/';
        $iconUrl = 'https://example.minds.com/img1.png';
        $mediaUrl = 'https://example.minds.com/img2.png';

        $manualSendRequest = new ManualSendRequest(
            platform: $platform,
            token: $token,
            title: $title,
            body: $body,
            uri: $uri,
            iconUrl: $iconUrl,
            mediaUrl: $mediaUrl
        );

        $streamInterface->getContents()
            ->shouldBeCalled()
            ->willReturn(json_encode(['error' => 'message']));

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($streamInterface);
            
        $this->apnsService->request($token, [], [
            'aps' => [
                "mutable-content" => 1,
                'alert' => [
                    'title' => $title,
                    'body' => $body
                ],
                'badge' => '',
            ],
            'uri' => $uri,
            'largeIcon' => $iconUrl
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->shouldThrow(new ServerErrorException('An unexpected error has occurred'))
            ->duringSend($manualSendRequest);
    }

    public function it_should_handle_empty_response_when_sending_to_ios(
        ResponseInterface $response,
        StreamInterface $streamInterface
    ): void {
        $platform = PushNotificationPlatformEnum::IOS;
        $token = 'token';
        $title = 'title';
        $body = 'body';
        $uri = 'https://example.minds.com/';
        $iconUrl = 'https://example.minds.com/img1.png';
        $mediaUrl = 'https://example.minds.com/img2.png';

        $manualSendRequest = new ManualSendRequest(
            platform: $platform,
            token: $token,
            title: $title,
            body: $body,
            uri: $uri,
            iconUrl: $iconUrl,
            mediaUrl: $mediaUrl
        );

        $streamInterface->getContents()
            ->shouldBeCalled()
            ->willReturn(json_encode([]));

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($streamInterface);
            
        $this->apnsService->request($token, [], [
            'aps' => [
                "mutable-content" => 1,
                'alert' => [
                    'title' => $title,
                    'body' => $body
                ],
                'badge' => '',
            ],
            'uri' => $uri,
            'largeIcon' => $iconUrl
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->shouldThrow(new ServerErrorException('An unexpected error has occurred'))
            ->duringSend($manualSendRequest);
    }
}
