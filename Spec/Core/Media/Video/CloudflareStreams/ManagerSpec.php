<?php

namespace Spec\Minds\Core\Media\Video\CloudflareStreams;

use DateTimeImmutable;
use Minds\Core\Media\Video\CloudflareStreams\Manager;
use Minds\Core\Media\Video\CloudflareStreams\Client;
use Minds\Core\Media\Video\CloudflareStreams\TranscodeStatus;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp;
use GuzzleHttp\Psr7\Response;
use Minds\Core\Config\Config;
use Minds\Entities\Video;

use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Minds\Core\Media\Video\Source;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;

class ManagerSpec extends ObjectBehavior
{
    private $client;
    const CLOUDFLARE_ID = "cloudflare-id";

    public function let(Client $client)
    {
        $this->beConstructedWith($client);
        $this->client = $client;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_copy_video_to_cloudlare()
    {
        $video = new Video();
        $video->guid = 123;
    
        $this->client->request('POST', 'stream/copy', Argument::that(function ($payload) {
            return true;
        }))
            ->willReturn(new Response(200, [], json_encode([
                'result' => [
                    'uid' => 123
                ]
            ])));

        $this->copy($video, "file:///source-uri");
    }

    public function it_should_get_signed_sources()
    {
        $video = new Video();
        $video->guid = 123;
        $video->setCloudflareId(ManagerSpec::CLOUDFLARE_ID);

        $pem = "LS0tLS1CRUdJTiBSU0EgUFJJVkFURSBLRVktLS0tLQpNSUlFcGdJQkFBS0NBUUVBMFRqd2pPaVpXbUo0M3ZmM1RvNERvWG1YV3RKR05HeVhmaHl0dExhQmdGMStFUVdRCkRLaG9LYm9hS21xakNBc21za3V0YkxVN1BVOGRrUU5ER1p3S3VWczA4elNaNGt4aTR0RWdQUFp5dDdkWEMrbFkKUllveXJBR0Y0QVhoeTMyOWJIQ1AxSWxyQkIvQWtHZ25kTEFndW54WTByUmdjdk96aWF3NktKeEZuYzJVSzBXVQo4YjBwNEtLSEdwMUtMOWRrMFdUOGRWWXFiZVJpSmpDbFVFbWg4eXY5Q2xPVmFTNEt4aVg2eFRRNERadzZEYUpmCklWM1F0Tmd2cG1ieWxOSmFQSG5zc3JodDJHS1A5NjJlS2poUVJsaWd2SFhKTE9uSm9KZkxlSUVIWitpeFdmY1QKRE1IOTJzR3ZvdzFET2p4TGlDTjF6SEsraDdiTG9YVGlMZ2M0a3dJREFRQUJBb0lCQVFEQ0lCclNJMTlteGNkdwoycExVaUdCR0N4T3NhVDVLbGhkYUpESG9ZdzUxbEVuTWNXVGUyY01NTkdqaXdsN1NyOFlQMkxmcERaOFJtNzdMCk5rT2tGMnk3M3l5YUhFeEw5S1FyMys0Um9ubCtqTlp2YnV0QVdxSDVodEE0dER4MUd3NE85OEg4YWlTcGh1eWQKRUliTGRrQm54OGlDZUdxbFBnbHZ6Q1dLV0xVZlhGbXplMkF5UjBzaWMyYXZRLzZyclYwb3pDdGQ1T0Vod093agphaCs3N1dZV1l0bkEraDhXZVZreWcvdG44UTJJOXo5ZVJYdlZxR2sxMDZLcWRtZFdiU2tIZzA4cFRUSGhVM2paCnMvZGNjdEdOMWFFanlUQWY0QzdHT2lrcUd1MGFTaW1aeDFOM2RWQzBobngySjJtdlhNQ0VtZ0g3TjVnZUxWUFAKOWdkQjdBQkJBb0dCQU5sT2hGQVhaTHV6Y0Ftczl1K3AxM05STWRFOHpIK2ZFaFBrbk9zZ21Xb3VqUzkxQTRtZgpuK01oN3d5bTZoVU1DbDk2WUNMNGtPM0RUMmlYWlRqTXZuMHBoVEx1MXNYcGxWNDJuamRnZGd3cFBEM0FnL1Y5ClVvV2hxdVhoa1I3RFpsUGg5Nmk1aEE0M1BvbTVPQm9BektJbEcrT3ZKUkhhZEVveC9jSmZScFd2QW9HQkFQWjUKNnNmWDdESElCNEtBczRmMWRuNGZJUkMweUF2WVdCL1R3UzZHUWVoNFRFbDVuSkQwWk9ZRVdUbVVBK3pPanZTNApuM09tZ2xNQTU5SGd1ZW13QXVRcEtwWFBOcFUvTERJaThtNnpmTUpvL3E5M0NOQlFQZngzZGh4ZVh4OXE2Mzg3Cm84QWxkOE42RGs4TThjRis3SlNaeUVJODJzLzdpdGRseXA2bFdLaGRBb0dCQUtnU0VrUGYxQWxZdjA2OGVFRGwKRzc0VkRuTEdrMlFobzltKzk1N2psOFNJUEtwMzFrU2JNUTU3TUdpWXNIT1czRzc4TjE3VTRVTUR6R2NZc1RFOQpLaGVrQldGZldMMjU2OHp5Y1d4akx1bzQrbDdJaDBkWHBudTBqbms5L1AvT0lWYS9iczBRcnhKUHFBN2RNb2JxCkYxdFJXRURCTmVxWkMxaFhVZTBEdzVRQkFvR0JBSjdBQ2NNcnhKcVBycDZVakkyK1FOS2M5Q3dSZEdPRXRjWFMKR3JQL2owWE83YnZKVTFsZHYvc1N3L0U4NzRZL3lIM0F5QnF5SFhDZXZiRkZZQmt1MzczYThlM0pwK3RhNC9scQozdUVFUkEvbmxscW5mWXJHbEJZZlQzaVlKQVpWVkZiL3I4bWJtRmJVTDVFazBqV0JyWmxNcjFwU1hkRGx3QmhhCkhMWXY0em1WQW9HQkFLQmw0cFNnbkNSTEJMUU9jWjhXQmhRSjAwZDZieFNrTGNpZ0xUNFJvY3RwNTY1SHJPMDAKSVFLdElTaEg1a2s3SVRHdUYvOERXZEN2djBMYnhvZVBJc2NFaStTaXk5WDZwWENPaS8xa2FyYVU5U3BpZ3czago3YjVlUVV0UlovTkIycVJwc3EzMEdCUENqanhudEVmK2lqelhUS0xNRndyUDhBMTlQNzRONGVTMAotLS0tLUVORCBSU0EgUFJJVkFURSBLRVktLS0tLQo";
        $this->client->request('POST', 'stream/keys', Argument::that(function ($payload) {
            return true;
        }))
            ->willReturn(new Response(200, [], json_encode([
                'result' => [
                    'id' => 'key-id',
                    'pem' => $pem,
                ]
            ])));

        $sources = $this->getSources($video);

        $jwtConfig = JWT\Configuration::forSymmetricSigner(new Sha256, InMemory::plainText(base64_decode($pem, true)));

        $jwtBuilder = $jwtConfig->builder();
        $jwtBuilder->withClaim('kid', 'key-id');
        $jwtBuilder->relatedTo(ManagerSpec::CLOUDFLARE_ID);
        $jwtBuilder->expiresAt(new DateTimeImmutable("+3600 seconds"));

        $expectedToken = (string) $jwtBuilder->getToken($jwtConfig->signer(), $jwtConfig->signingKey())->toString();
        $expectedSrc = "https://videodelivery.net/$expectedToken/manifest/video.m3u8";
        // $sources[0]->getSrc()
        //     ->shouldBe($expectedSrc);
    }

    public function it_should_throw_error_if_video_didnt_have_cloudflare_id()
    {
        $video = new Video();
        $video->guid = 123;
        $video->setCloudflareId(null);

        $this->shouldThrow()->during('getVideoTranscodeStatus', [$video]);
    }

    public function it_should_return_completed_status()
    {
        $video = new Video();
        $video->guid = 123;
        $video->setCloudflareId(ManagerSpec::CLOUDFLARE_ID);

        $this->mockTranscodeResponse('ready', 100);

        $this->getVideoTranscodeStatus($video)->getState()->shouldReturn(TranscodeStates::COMPLETED);
    }

    public function it_should_return_transcoding_status()
    {
        $video = new Video();
        $video->guid = 123;
        $video->setCloudflareId(ManagerSpec::CLOUDFLARE_ID);

        $this->mockTranscodeResponse('inprogress', 20);

        $this->getVideoTranscodeStatus($video)->getState()->shouldReturn(TranscodeStates::TRANSCODING);
    }

    public function it_should_return_failed_status()
    {
        $video = new Video();
        $video->guid = 123;
        $video->setCloudflareId(ManagerSpec::CLOUDFLARE_ID);

        $this->mockTranscodeResponse('failed', 0);

        $this->getVideoTranscodeStatus($video)->getState()->shouldReturn(TranscodeStates::FAILED);
    }

    private function mockTranscodeResponse(string $status = 'ready', int $pct = 0): void
    {
        $this->client->request('GET', 'stream/' . ManagerSpec::CLOUDFLARE_ID, Argument::that(function ($payload) {
            return true;
        }))
            ->willReturn(new Response(200, [], json_encode([
                'result' => [
                    'status' => [
                        'state' => $status,
                        'pct' => $pct
                    ]
                ]
            ])));
    }
}
