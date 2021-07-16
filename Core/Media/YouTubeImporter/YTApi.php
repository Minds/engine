<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Di\Di;
use Minds\Core\Data\cache\PsrWrapper;
use GuzzleHttp;

class YTApi
{
    /** @var PsrWrapper */
    protected $cache;

    /** @var GuzzleHttp\Client */
    protected $httpClient;

    /**
     * @param PsrWrapper $cache
     */
    public function __construct($cache = null, GuzzleHttp\Client $httpClient = null)
    {
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
    }

    /**
     * TODO: Typed response
     * Fetches the data of a YouTube Video
     * @param string $ytVideoId
     * @return array
     */
    public function getVideoInfo(string $ytVideoId): array
    {
        $cacheKey = "ytimporter:api-get_video_info-id:$ytVideoId";

        if ($cached = $this->cache->get($cacheKey)) {
            $info = unserialize($cached);
        } else {
            $queryParams = http_build_query([
                'video_id' => $ytVideoId,
                'html5' => 1,
                'c' => 'TVHTML5',
                'cver' => '6.20180913'
            ]);
            $response = $this->httpClient->get("https://youtube.com/get_video_info?$queryParams");
            parse_str($response->getBody()->getContents(), $ytInfo);

            $info = json_decode($ytInfo['player_response'], true);

            if (!$info) {
                throw new QuotaExceededException();
            }

            if ($info) {
                $this->cache->set($cacheKey, serialize($info), 86400); // 1 day cache
            }
        }

        return $info;
    }
}
