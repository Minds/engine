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
 * @method int|string getLogoGuid()
 * @method Settings setLogoGuid(int|string $logoGuid)
 * @method string getFooterText()
 * @method Settings setFooterText(string $footerText)
 * @method array getFooterLinks()
 * @method Settings setFooterLinks(array $footerLinks)
 * @method array getTagList()
 * @method Settings setTagList(array $footerLinks)
 * @method string getBackgroundImage()
 * @method Settings setBackgroundImage(string $backgroundImage)
 * @method string getLogoImage()
 * @method Settings setLogoImage(string $logoImage)
 */
class Settings implements JsonSerializable
{
    use MagicAttributes;

    const DEFAULT_TEXT_COLOR = '#000000';

    const DEFAULT_PRIMARY_COLOR = '#4690df';

    const DEFAULT_PLAIN_BACKGROUND_COLOR = '#ffffff';

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

    /** @var int */
    protected $logoGuid;

    /** @var string */
    protected $backgroundImage;

    /** @var string */
    protected $logoImage;

    /** @var string */
    protected $footerText;

    /** @var array */
    protected $footerLinks = [];

    /** @var array */
    protected $tagList = [];

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
            'text_color' => $this->textColor ?: static::DEFAULT_TEXT_COLOR,
            'primary_color' => $this->primaryColor ?: static::DEFAULT_PRIMARY_COLOR,
            'plain_background_color' => $this->plainBackgroundColor ?: static::DEFAULT_PLAIN_BACKGROUND_COLOR,
            'footer_text' => $this->footerText,
            'footer_links' => $this->footerLinks,
            'tag_list' => $this->tagList,
            'logo_guid' => (string) $this->logoGuid,
            'background_image' => $this->backgroundImage,
            'logo_image' => $this->logoImage,
            'styles' => [
                'text_color' => $this->textColor ?: static::DEFAULT_TEXT_COLOR,
                'primary_color' => $this->primaryColor ?: static::DEFAULT_PRIMARY_COLOR,
                'plain_background_color' => $this->plainBackgroundColor ?: static::DEFAULT_PLAIN_BACKGROUND_COLOR,
                'transparent_background_color' => sprintf("%sa0", $this->plainBackgroundColor ?: static::DEFAULT_PLAIN_BACKGROUND_COLOR),
            ],
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
