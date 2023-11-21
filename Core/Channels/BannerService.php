<?php
declare(strict_types=1);

namespace Minds\Core\Channels;

use ElggFile;
use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Log\Logger;
use Minds\Entities\File;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Entities\User;

/**
 * Servive for the setting and retrieval of banners.
 */
class BannerService
{
    /** Paths to be chosen between when a user has no banner set. */
    public const DEFAULT_BANNER_PATHS = [
        'Assets/banners/0.jpg',
        'Assets/banners/1.jpg',
        'Assets/banners/2.jpg',
        'Assets/banners/3.jpg',
        'Assets/banners/4.jpg',
        'Assets/banners/5.jpg',
        'Assets/banners/6.jpg',
        'Assets/banners/7.jpg',
        'Assets/banners/8.jpg',
        'Assets/banners/9.jpg',
    ];
 
    public function __construct(
        private ImagickManager $imagickManager,
        private SaveAction $saveAction,
        private Config $config,
        private Logger $logger
    ) {
    }

    /**
     * Gets the banner file for a user.
     * @param string $userGuid - guid of user to get banner for.
     * @return ElggFile - banner file.
     */
    public function getFile(string $userGuid): ElggFile
    {
        $file = new ElggFile();
        $file->setFilename("banners/{$userGuid}.jpg");
        $file->owner_guid = $userGuid;
        return $file;
    }

    /**
     * Uploads a banner for a user.
     * @param string $path - path to banner to upload from.
     * @param User $user - user to get banner for.
     * @return bool - success.
     */
    public function upload(string $path, User $user): bool
    {
        try {
            $imageFile = $this->imagickManager->setImage($path)
                ->autorotate()
                ->resize(2000, 10000)
                ->getJpeg();

            $file = new File();
            $file->setFilename("banners/{$user->getGuid()}.jpg");
            $file->owner_guid = $user->getGuid();
            $file->open('write');
            $file->write($imageFile);
            $file->close();

            $user->icontime = time();
            $this->saveAction
                ->setEntity($user)
                ->withMutatedAttributes(['icontime'])
                ->save(true);

            return true;
        } catch(\Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * Gets the default banner content for the user - to be used
     * when no banner is set to get a seeded default banner.
     * @param int $userGuid - guid of user to get banner for.
     * @return string - banner content.
     */
    public function getDefaultBannerContent(int $userGuid = 0): string
    {
        return file_get_contents(
            $this->config->get('path') . 'engine/' . $this->getSeededBannerPath(
                $userGuid
            )
        );
    }

    /**
     * Derives the seeded banner path for the user.
     * @param int $userGuid - guid to use as seed.
     * @return string - banner path.
     */
    private function getSeededBannerPath(int $userGuid = 0): string
    {
        return self::DEFAULT_BANNER_PATHS[$userGuid % count(self::DEFAULT_BANNER_PATHS)];
    }
}
