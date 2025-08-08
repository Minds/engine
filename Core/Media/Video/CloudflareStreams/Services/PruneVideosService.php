<?php
namespace Minds\Core\Media\Video\CloudflareStreams\Services;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Video\CloudflareStreams\Client;
use Minds\Entities\User;
use Minds\Entities\Video;

class PruneVideosService
{
    protected bool $dryRun = false;

    public function __construct(
        private Client $cfClient,
        private Logger $logger,
        private EntitiesBuilder $entitiesBuilder,
    ) {
        
    }

    /**
     * Run in dry run mode
     */
    public function withDryRun(bool $dryRun): PruneVideosService
    {
        $instance = clone $this;
        $instance->dryRun = $dryRun;
        return $instance;
    }

    public function process(): void
    {
        $i = 0;
        foreach ($this->getVideos() as $row) {
            ++$i;

            $uid = $row['uid'];

            $tenantId = $row['meta']['tenant_id'] ?? null;

            if ($tenantId) {
                // Skip tenant videos from pruning
                $this->logger->info('Tenant video, skipping');
                continue;
            }

            $ownerGuid = $row['meta']['owner_guid'] ?? null;
            if (!$ownerGuid) {
                // No owner? Just delete the video then
                $this->logger->info('No owner, deleting ' . $uid);
                continue;
            }

            $owner = $this->entitiesBuilder->single($ownerGuid);
            if (!$owner instanceof User) {
                $this->logger->info('Owner not found, deleting video ' . $uid, [
                    'owner_guid' => $ownerGuid,
                    'created' => $row['created']
                ]);
                
                $this->delete($uid);
                continue;
            }
            
            $videoGuid = $row['meta']['guid'];
            $video = $this->entitiesBuilder->single($videoGuid);

            if (!$video instanceof Video) {
                $this->logger->info('Video not found, deleting video ' . $uid, [
                    'owner_guid' => $owner->getGuid(),
                    'video_guid' => $videoGuid,
                    'created' => $row['created']
                ]);

                $this->delete($uid);
                continue;
            }
            

            // Valid! A video that is less that 30 days ago (+10 day grace period)
            // will not be rpune
            if ($video->time_created > strtotime('40 days ago')) {
                continue;
            }

            // No valid plus (or outside the 30 day grace period)
            if (
                (int) $owner->plus_expires < strtotime('30 days ago')
                &&  (int) $owner->pro_expires < strtotime('30 days ago')
            ) {
                $this->logger->info(
                    'Not a valid plus user and video older than 30 days old ' . $uid,
                    [
                    'owner_guid' => $owner->getGuid(),
                    'video_guid' => $video->getGuid(),
                    'created' => $row['created']
                ]
                );

                $this->delete($uid);
                continue;
            }
        
        }
    }

    /**
     * Returns all the videos
     */
    private function getVideos(string $cursor= ""): iterable
    {
        while (true) {
            $queryParams = http_build_query([
                'asc' => true,
                'start' => $cursor,
            ]);
            $response = $this->cfClient->request('GET', 'stream?' . $queryParams);
            $json = json_decode($response->getBody()->getContents(), true);
            
            foreach ($json['result'] as $row) {
                $cursor = $row['created'];
                yield $row;
            }
        }
    }

    /**
     * Tag the object storage source
     */
    private function delete(string $uid): bool
    {
        if ($this->dryRun) {
            return true;
        }
        try {
            $response = $this->cfClient->request('DELETE', "stream/$uid");
            return !!$response->getBody()->getContents();
        } catch (GuzzleException $e) {
            $this->logger->error($e->getMessage());
            if ($e->getCode() === 429) {
                sleep(30);
            }
            return false;
        }
    }

}
