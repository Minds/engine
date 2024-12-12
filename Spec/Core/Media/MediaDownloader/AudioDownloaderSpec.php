<?php

namespace Spec\Minds\Core\Media\MediaDownloader;

use GuzzleHttp\Client;
use Minds\Core\Log\Logger;
use Minds\Core\Media\MediaDownloader\AudioDownloader;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;

class AudioDownloaderSpec extends ObjectBehavior
{
    private $clientMock;
    private $loggerMock;

    public function let(
        Client $clientMock,
        Logger $loggerMock,
    ) {
        $this->clientMock = $clientMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith($clientMock, $loggerMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AudioDownloader::class);
    }

    public function it_should_download_audio(
        ResponseInterface $responseMock
    ) {
        $url = "https://example.minds.com/audio.mp3";

        $this->clientMock->get($url, [
            'timeout' => AudioDownloader::REQUEST_TIMEOUT_SECONDS,
            'headers' => [
                'Accept' => '*/*'
            ],
            'allow_redirects' => [
                'max' => 10
            ]
        ])
            ->willReturn($responseMock);

        $responseMock->getHeader('Content-Type')
            ->willReturn(['audio/mpeg']);

        $this->download($url)
            ->shouldReturn($responseMock);
    }

    public function it_should_throw_exception_for_invalid_content_type(
        ResponseInterface $responseMock
    ) {
        $url = "https://example.minds.com/other.txt";

        $this->clientMock->get($url, Argument::any())
            ->willReturn($responseMock);

        $responseMock->getHeader('Content-Type')
            ->willReturn(['text/plain']);

        $this->loggerMock->error(Argument::type('string'))
            ->shouldBeCalled();

        $this->shouldThrow(\Exception::class)
            ->duringDownload($url);
    }

    public function it_should_handle_download_errors()
    {
        $url = "https://example.minds.com/audio.mp3";
        $exception = new \Exception("Download failed");

        $this->clientMock->get($url, Argument::any())
            ->willThrow($exception);

        $this->loggerMock->error($exception->getMessage())
            ->shouldBeCalled();

        $this->shouldThrow(\Exception::class)
            ->duringDownload($url);
    }

    public function it_should_handle_missing_content_type_header(
        ResponseInterface $responseMock
    ) {
        $url = "https://example.minds.com/audio.mp3";

        $this->clientMock->get($url, Argument::any())
            ->willReturn($responseMock);

        $responseMock->getHeader('Content-Type')
            ->willReturn([]);

        $this->loggerMock->error(Argument::type('string'))
            ->shouldBeCalled();

        $this->shouldThrow(\Exception::class)
            ->duringDownload($url);
    }
}
