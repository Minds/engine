<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Di\Di;
use Minds\Core\Data\Cache\PsrWrapper;

class YTApi
{
    /** @var PsrWrapper */
    protected $cache;

    /**
     * @param PsrWrapper $cache
     */
    public function __construct($cache = null)
    {
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
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
            // get and decode the data
            parse_str(file_get_contents("https://youtube.com/get_video_info?video_id=" . $ytVideoId), $ytInfo);

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
