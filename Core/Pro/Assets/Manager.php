<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Pro\Assets;

use ElggFile;
use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Media\Imagick\Manager as ImageManager;
use Minds\Entities\User;
use Zend\Diactoros\UploadedFile;

class Manager
{
    /** @var ImageManager */
    protected $imageManager;

    /** @var string */
    protected $type;

    /** @var User */
    protected $user;

    /** @var User */
    protected $actor;

    /**
     * Manager constructor.
     * @param ImageManager $imageManager
     */
    public function __construct(
        $imageManager = null
    ) {
        $this->imageManager = $imageManager ?: Di::_()->get('Media\Imagick\Manager');
    }

    /**
     * @param string $type
     * @return Manager
     */
    public function setType(string $type): Manager
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param User $user
     * @return Manager
     */
    public function setUser(User $user): Manager
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param User $actor
     * @return Manager
     */
    public function setActor(User $actor): Manager
    {
        $this->actor = $actor;
        return $this;
    }

    /**
     * @param UploadedFile $file
     * @param Asset|null $asset
     * @return bool
     * @throws Exception
     */
    public function set(UploadedFile $file, Asset $asset = null)
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        } elseif (!$this->type || !in_array($this->type, Asset::TYPES, true)) {
            throw new Exception('Invalid asset type');
        }

        // Load image

        $this->imageManager
            ->setImageFromBlob(
                $file->getStream()->getContents(),
                $file->getClientFilename()
            );

        // Setup asset

        if (!$asset) {
            $asset = new Asset();
        }

        $asset
            ->setType($this->type)
            ->setUserGuid($this->user->guid);

        // Handle asset type

        switch ($this->type) {
            case 'logo':
                $blob = $this->imageManager
                    ->resize(1920, 1080, false, false) // Max: 2K
                    ->getPng();
                break;

            case 'background':
                $blob = $this->imageManager
                    ->autorotate()
                    ->resize(3840, 2160, false, false) // Max: 4K
                    ->getJpeg(85);
                break;

            default:
                throw new Exception('Invalid asset type handler');
        }

        $file = $asset->getFile();
        $file->open('write');
        $file->write($blob);
        $file->close();

        return true;
    }
}
