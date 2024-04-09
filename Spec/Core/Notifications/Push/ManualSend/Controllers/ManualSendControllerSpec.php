<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Notifications\Push\ManualSend\Controllers;

use Minds\Core\Notifications\Push\ManualSend\Controllers\ManualSendController;
use Minds\Core\Notifications\Push\ManualSend\Enums\PushNotificationPlatformEnum;
use Minds\Core\Notifications\Push\ManualSend\Interfaces\ManualSendPayloadValidatorInterface;
use Minds\Core\Notifications\Push\ManualSend\Interfaces\ManualSendServiceInterface;
use Minds\Core\Notifications\Push\ManualSend\Models\ManualSendRequest;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

class ManualSendControllerSpec extends ObjectBehavior
{
    public Collaborator $service;
    public Collaborator $payloadValidator;

    public function let(
        ManualSendServiceInterface $service,
        ManualSendPayloadValidatorInterface $payloadValidator
    ) {
        $this->service = $service;
        $this->payloadValidator = $payloadValidator;
        $this->beConstructedWith($service, $payloadValidator);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ManualSendController::class);
    }

    public function it_should_send_for_android(ServerRequestInterface $request)
    {
        $platform = 'android';
        $token = 'token';
        $title = 'title';
        $body = 'body';
        $uri = 'https://example.minds.com/';
        $iconUrl = 'https://example.minds.com/img1.png';
        $mediaUrl = 'https://example.minds.com/img2.png';
        $payload = [
            'platform' => $platform,
            'token' => $token,
            'title' => $title,
            'body' => $body,
            'uri' => $uri,
            'icon_url' => $iconUrl,
            'media_url' => $mediaUrl
        ];

        $request->getParsedBody()
            ->shouldBeCalled()
            ->willReturn([
                'platform' => $platform,
                'token' => $token,
                'title' => $title,
                'body' => $body,
                'uri' => $uri,
                'icon_url' => $iconUrl,
                'media_url' => $mediaUrl
            ]);

        $this->payloadValidator->validate($payload)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->service->send(
            new ManualSendRequest(
                platform: PushNotificationPlatformEnum::ANDROID,
                token: $token,
                title: $title,
                body: $body,
                uri: $uri,
                iconUrl: $iconUrl,
                mediaUrl: $mediaUrl
            )
        )->shouldBeCalled()
            ->willReturn(true);

        $this->send($request);
    }

    public function it_should_send_for_ios(ServerRequestInterface $request)
    {
        $platform = 'ios';
        $token = 'token';
        $title = 'title';
        $body = 'body';
        $uri = 'https://example.minds.com/';
        $iconUrl = 'https://example.minds.com/img1.png';
        $mediaUrl = 'https://example.minds.com/img2.png';
        $payload = [
            'platform' => $platform,
            'token' => $token,
            'title' => $title,
            'body' => $body,
            'uri' => $uri,
            'icon_url' => $iconUrl,
            'media_url' => $mediaUrl
        ];

        $request->getParsedBody()
            ->shouldBeCalled()
            ->willReturn([
                'platform' => $platform,
                'token' => $token,
                'title' => $title,
                'body' => $body,
                'uri' => $uri,
                'icon_url' => $iconUrl,
                'media_url' => $mediaUrl
            ]);

        $this->payloadValidator->validate($payload)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->service->send(
            new ManualSendRequest(
                platform: PushNotificationPlatformEnum::IOS,
                token: $token,
                title: $title,
                body: $body,
                uri: $uri,
                iconUrl: $iconUrl,
                mediaUrl: $mediaUrl
            )
        )->shouldBeCalled()
            ->willReturn(true);

        $this->send($request);
    }

    public function it_should_NOT_when_there_are_validation_errors(ServerRequestInterface $request)
    {
        $platform = 'android';
        $token = 'token';
        $title = 'title';
        $body = 'body';
        $uri = 'https://example.minds.com/';
        $iconUrl = 'https://example.minds.com/img1.png';
        $mediaUrl = 'https://example.minds.com/img2.png';
        $payload = [
            'platform' => $platform,
            'token' => $token,
            'title' => $title,
            'body' => $body,
            'uri' => $uri,
            'icon_url' => $iconUrl,
            'media_url' => $mediaUrl
        ];

        $request->getParsedBody()
            ->shouldBeCalled()
            ->willReturn([
                'platform' => $platform,
                'token' => $token,
                'title' => $title,
                'body' => $body,
                'uri' => $uri,
                'icon_url' => $iconUrl,
                'media_url' => $mediaUrl
            ]);

        $this->payloadValidator->validate($payload)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->payloadValidator->getErrors()
            ->shouldBeCalled()
            ->willReturn(new ValidationErrorCollection([]));

        $this->service->send(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(UserErrorException::class)->duringSend($request);
    }
}
