<?php
/**
 * Settings
 * @author edgebal
 */

namespace Minds\Core\Pro;

use JsonSerializable;
use Minds\Traits\MagicAttributes;

/**
 * Class Settings
 * @package Minds\Core\Pro
 * @method int|string getUserGuid()
 * @method Settings setUserGuid(int|string $userGuid)
 * @method string getDomain()
 * @method Settings setDomain(string $domain)
 * @method string getTitle()
 * @method Settings setTitle(string $title)
 * @method string getHeadline()
 * @method Settings setHeadline(string $headline)
 * @method string getTextColor()
 * @method Settings setTextColor(string $textColor)
 * @method string getPrimaryColor()
 * @method Settings setPrimaryColor(string $primaryColor)
 * @method string getPlainBackgroundColor()
 * @method Settings setPlainBackgroundColor(string $plainBackgroundColor)
 * @method string getBackgroundImage()
 * @method Settings setBackgroundImage(string $backgroundImage)
 * @method string getLogoImage()
 * @method Settings setLogoImage(string $logoImage)
 */
class Settings implements JsonSerializable
{
    use MagicAttributes;

    /** @var int */
    protected $userGuid;

    /** @var string */
    protected $domain;

    /** @var string */
    protected $title;

    /** @var string */
    protected $headline;

    /** @var string */
    protected $textColor;

    /** @var string */
    protected $primaryColor;

    /** @var string */
    protected $plainBackgroundColor;

    /** @var string */
    protected $backgroundImage;

    /** @var string */
    protected $logoImage;

    /**
     * @return array
     */
    public function export()
    {
        return [
            'user_guid' => (string) $this->userGuid,
            'domain' => $this->domain,
            'title' => $this->title,
            'headline' => $this->headline,
            'text_color' => $this->textColor,
            'primary_color' => $this->primaryColor,
            'plain_background_color' => $this->plainBackgroundColor,
            'background_image' => $this->backgroundImage,
            'logo_image' => $this->logoImage,
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->export();
    }
}
