<?php
namespace Minds\Core\Channels;

use ElggFile;
use Minds\Core\Config\Config;
use Minds\Core\Data\Call;
use Minds\Core\Di\Di;
use Minds\Core\Media\Proxy\Download;
use Minds\Core\Media\Imagick;
use Minds\Entities\User;

class AvatarService
{
    /** @var User */
    private User $user;

    public function __construct(
        protected ?Download $downloadService = null,
        protected ?Config $config = null,
        protected ?Imagick\Manager $imagickManager = null,
        protected ?Call $entitiesDb = null
    ) {
        $this->downloadService ??= new Download();
        $this->config ??= Di::_()->get('Config');
        $this->imagickManager ??= Di::_()->get('Media\Imagick\Manager');
        $this->entitiesDb ??= new Call('entities');
    }

    /**
     * The user/channel to set for context
     * @param User $user
     * @return AvatarService
     */
    public function withUser(User $user): AvatarService
    {
        $service = clone $this;
        $service->user = $user;
        return $service;
    }

    /**
     * Imports an avatar from a url
     * For best security practices, ensure that you have set the 'http_proxy' value
     * in config.
     * @param string $url
     * @return bool
     */
    public function createFromUrl(string $url): bool
    {
        $tmpfile = tmpfile();

        $binaryString = $this->downloadService->setSrc($url)
            ->downloadBinaryString();

        fputs($tmpfile, $binaryString);

        return $this->resizeAndSave(stream_get_meta_data($tmpfile)['uri']);
    }

    /**
     * Uploads avatar from filename
     * @param string $filename
     * @return bool
     */
    public function createFromFile(string $filename): bool
    {
        return $this->resizeAndSave($filename);
    }

    /**
     * Will resize and save avatar
     * @param resource|string $file
     * @return bool
     */
    protected function resizeAndSave(mixed $originalFile): bool
    {
        $userGuid = $this->user->getGuid();
        $iconSizes = $this->config->get('icon_sizes');

        $files = [];
        foreach ($iconSizes as $name => $size_info) {
            $this->imagickManager->setImage($originalFile)
                        ->autorotate()
                        ->resize($size_info['w'], $size_info['h'], $size_info['upscale'], $size_info['square']);

            if ($blob = $this->imagickManager->getJpeg()) {
                //@todo Make these actual entities.  See exts #348.
                $file = new ElggFile();
                $file->owner_guid = $userGuid;
                $file->setFilename("profile/{$userGuid}{$name}.jpg");
                $file->open('write');
                $file->write($blob);
                $file->close();
                $files[] = $file;
            } else {
                // cleanup on fail
                foreach ($files as $file) {
                    $file->delete();
                }

                return false;
            }
        }

        return $this->patchUser();
    }

    /**
     * Direct patch to the user entity to avoid overwriting stale data
     * @return bool
     */
    protected function patchUser(): bool
    {
        return $this->entitiesDb->insert($this->user->getGuid(), [
                    'x1' => 0,
                    'x2' => 0,
                    'y1' => 0,
                    'y2' => 0,
                    'icontime' => time(),
                    'last_avatar_upload' => time(),
                ]);
    }
}
