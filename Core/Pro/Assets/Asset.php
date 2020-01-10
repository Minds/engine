<?php
/**
 * Info
 * @author edgebal
 */

namespace Minds\Core\Pro\Assets;

use ElggFile;
use Exception;
use Minds\Traits\MagicAttributes;

/**
 * Class Asset
 * @package Minds\Core\Pro\Assets
 * @method string getType()
 * @method int|string getUserGuid()
 * @method Asset setUserGuid(int|string $userGuid)
 */
class Asset
{
    use MagicAttributes;

    /** @var string */
    protected $type;

    /** @var int|string */
    protected $userGuid;

    /** @var string[] */
    const TYPES = ['logo', 'background'];

    /**
     * @param string $type
     * @return Asset
     * @throws Exception
     */
    public function setType(string $type): Asset
    {
        if (!in_array($type, static::TYPES, true)) {
            throw new Exception('Invalid Asset type');
        }

        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getExt(): string
    {
        switch ($this->type) {
            case 'logo':
                return 'png';

            case 'background':
                return 'jpg';
        }

        throw new Exception('Invalid Asset');
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getMimeType(): string
    {
        switch ($this->type) {
            case 'logo':
                return 'image/png';

            case 'background':
                return 'image/jpg';
        }

        throw new Exception('Invalid Asset');
    }

    /**
     * @return ElggFile
     * @throws Exception
     */
    public function getFile(): ElggFile
    {
        $file = new ElggFile();

        $file->owner_guid = $this->userGuid;
        $file->setFilename(sprintf("pro/%s.%s", $this->type, $this->getExt()));

        return $file;
    }
}
