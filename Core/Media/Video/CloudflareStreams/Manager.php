<?php
namespace Minds\Core\Media\Video\CloudflareStreams;

use DateTimeImmutable;
use Minds\Core\Config;
use Minds\Entities\Video;
use Minds\Core\Di\Di;
use Minds\Core\Data\cache\PsrWrapper;
use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Minds\Core\Media\Video\Source;

class Manager
{
    /** @var Client */
    protected $client;

    /** @var Config */
    protected $config;

    /** @var PsrWrapper */
    protected $cache;

    public function __construct($client = null, $config = null)
    {
        $this->client = $client ?? new Client();
        $this->config = $config ?? Di::_()->get('Config');
        $this->cache = $cacher ?? Di::_()->get('Cache');
    }

    /**
     * Copies source file to streams api
     * @param Video $video
     * @param string $sourceUri
     * @return void
     */
    public function copy(Video $video, string $sourceUri): void
    {
        $payload = [
            'url' => $sourceUri,
            'meta' => [
                'guid' => $video->getGuid(),
                // "name" => $video->getTitle() ?: '',
                'owner_guid' => $video->getOwnerGUID(),
            ],
            'thumbnailTimestampPct' => 0.5,
            'requireSignedURLs' => true,
        ];

        $response = $this->client->request('POST', 'stream/copy', $payload);

        $json = json_decode($response->getBody(), true);

        $uid = $json['result']['uid'];
        $video->setCloudflareId($uid);
    }

    /**
     * @param Video $video
     * @return Source[]
     */
    public function getSources(Video $video): array
    {
        $signedToken = $this->getSigningToken($video->getCloudflareId());

        return [
            (new Source())
                ->setType('video/hls')
                ->setSrc("https://videodelivery.net/$signedToken/manifest/video.m3u8")
        ];
    }

    /**
     * @param string $videoId
     * @return string
     */
    protected function getSigningToken($videoId): string
    {
        $signingKey = $this->getSigningKey();

        $jwtBuilder = new JWT\Builder;
        $jwtBuilder->withClaim('kid', $signingKey->getId());
        $jwtBuilder->relatedTo($videoId);
        $jwtBuilder->expiresAt(new DateTimeImmutable('+1 hour'));
    
        $token = (string) $jwtBuilder->getToken(new Sha256, new Key(base64_decode($signingKey->getPem(), true)));

        return $token;
    }

    /**
     * Returns (and caches) a signing key
     *
     * TODO: Cleanup these keys with https://api.cloudflare.com/#stream-signing-keys-list-signing-keys]
     *
     * @return SigningKey
     */
    protected function getSigningKey(): SigningKey
    {
        if ($cached = $this->cache->get('cloudflare_signing_key')) {
            return unserialize($cached);
        }

        $response = $this->client->request('POST', 'stream/keys');

        $json = json_decode($response->getBody(), true);

        $signingKey = (new SigningKey())
                            ->setId($json['result']['id'])
                            ->setPem($json['result']['pem']);

        $this->cache->set('cloudflare_signing_key', serialize($signingKey));

        return $signingKey;
    }
}
