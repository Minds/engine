<?php
namespace Minds\Core\Media\YouTubeImporter;

class YTApi
{
    /**
     * TODO: Typed response
     * Fetches the data of a YouTube Video
     * @param string $ytVideoId
     * @return array
     */
    public function getVideoInfo(string $ytVideoId): array
    {
        // get and decode the data
        parse_str(file_get_contents("https://youtube.com/get_video_info?video_id=" . $ytVideoId), $info);

        return json_decode($info['player_response'], true);
    }
}
