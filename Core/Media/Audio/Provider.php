<?php

namespace Minds\Core\Media\Audio;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\SharedCache;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Media\Services\OciS3Client;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Events::class, fn (Di $di) => new Events(
            eventsDispatcher: $di->get('EventsDispatcher'),
            config: $di->get(Config::class),
        ));

        $this->di->bind(AudioService::class, fn (Di $di) =>
            new AudioService(
                audioAssetStorageService: $di->get(AudioAssetStorageService::class),
                audioRepository: $di->get(AudioRepository::class),
                audioThumbnailService: $di->get(AudioThumbnailService::class),
                fFMpeg: $di->get(FFMpeg::class),
                fFProbe: $di->get(FFProbe::class),
                actionEventsTopic: $di->get('EventStreams\Topics\ActionEventsTopic'),
                cache: $di->get(SharedCache::class),
            ));

        $this->di->bind(AudioRepository::class, fn (Di $di) =>
            new AudioRepository(
                mysqlHandler: $di->get(Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger'),
            ));

        $this->di->bind(AudioAssetStorageService::class, fn (Di $di) =>
            new AudioAssetStorageService(
                config: $di->get(Config::class),
                ociS3: $di->get(OciS3Client::class),
                osClient: $di->get(ObjectStorageClient::class),
            ));

        $this->di->bind(AudioThumbnailService::class, fn (Di $di) =>
            new AudioThumbnailService(
                audioAssetStorageService: $di->get(AudioAssetStorageService::class),
                imagickManager: $di->get('Media\Imagick\Manager'),
            ));

        $this->di->bind(AudioPsrController::class, fn (Di $di) =>
            new AudioPsrController(
                audioService: $di->get(AudioService::class),
                audioThumbnailService: $di->get(AudioThumbnailService::class),
                acl: $di->get('Security\ACL'),
            ));
    }
}
