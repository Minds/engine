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
 * @method string getTileRatio()
 * @method Settings setTileRatio(string $tileRatio)
 * @method int|string getLogoGuid()
 * @method Settings setLogoGuid(int|string $logoGuid)
 * @method string getFooterText()
 * @method Settings setFooterText(string $footerText)
 * @method array getFooterLinks()
 * @method Settings setFooterLinks(array $footerLinks)
 * @method array getTagList()
 * @method Settings setTagList(array $tagList)
 * @method string getScheme()
 * @method Settings setScheme(string $scheme)
 * @method string getBackgroundImage()
 * @method Settings setBackgroundImage(string $backgroundImage)
 * @method string getLogoImage()
 * @method Settings setLogoImage(string $logoImage)
 * @method array getFeaturedContent()
 * @method Settings setFeaturedContent(array $featuredContent)
 * @method string getCustomHead()
 * @method Settings setCustomHead(string $customHead)
 */
class Settings implements JsonSerializable
{
    use MagicAttributes;

    /** @var string */
    const DEFAULT_TEXT_COLOR = '#000000';

    /** @var string */
    const DEFAULT_PRIMARY_COLOR = '#4690df';

    /** @var string */
    const DEFAULT_PLAIN_BACKGROUND_COLOR = '#ffffff';

    /** @var string */
    const DEFAULT_TILE_RATIO = '16:9';

    /** @var array */
    const TILE_RATIOS = ['16:9', '16:10', '4:3', '1:1'];

    /** @var array */
    const COLOR_SCHEMES = ['light', 'dark'];

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
    protected $tileRatio = '16:9';

    /** @var string */
    protected $logoImage;

    /** @var string */
    protected $footerText;

    /** @var array */
    protected $footerLinks = [];

    /** @var array */
    protected $tagList = [];

    /** @var string */
    protected $scheme;

    /** @var array */
    protected $featuredContent = [];

    /** @var string */
    protected $customHead = '';

    /**
     * @return string
     */
    public function getOneLineHeadline(): string
    {
        return preg_replace("/\\r?\\n+/", ' ', $this->headline);
    }

    /**
     * @return array
     */
    public function export(): array
    {
        $textColor = $this->textColor ?: static::DEFAULT_TEXT_COLOR;
        $primaryColor = $this->primaryColor ?: static::DEFAULT_PRIMARY_COLOR;
        $plainBackgroundColor = $this->plainBackgroundColor ?: static::DEFAULT_PLAIN_BACKGROUND_COLOR;
        $tileRatio = $this->tileRatio ?: static::DEFAULT_TILE_RATIO;

        return [
            'user_guid' => (string) $this->userGuid,
            'domain' => $this->domain,
            'title' => $this->title,
            'headline' => $this->headline,
            'text_color' => $textColor,
            'primary_color' => $primaryColor,
            'plain_background_color' => $plainBackgroundColor,
            'tile_ratio' => $tileRatio,
            'footer_text' => $this->footerText,
            'footer_links' => $this->footerLinks,
            'tag_list' => $this->tagList,
            'logo_guid' => (string) $this->logoGuid,
            'background_image' => $this->backgroundImage,
            'logo_image' => $this->logoImage,
            'featured_content' => $this->featuredContent,
            'scheme' => $this->scheme,
            'custom_head' => $this->customHead,
            'one_line_headline' => $this->getOneLineHeadline(),
            'styles' => $this->buildStyles(),
        ];
    }

    /**
     * @return array
     */
    public function buildStyles(): array
    {
        $textColor = $this->textColor ?: static::DEFAULT_TEXT_COLOR;
        $primaryColor = $this->primaryColor ?: static::DEFAULT_PRIMARY_COLOR;
        $plainBackgroundColor = $this->plainBackgroundColor ?: static::DEFAULT_PLAIN_BACKGROUND_COLOR;
        $tileRatioPercentage = $this->calcTileRatioPercentage();

        return [
            'text_color' => $textColor,
            'primary_color' => $primaryColor,
            'plain_background_color' => $plainBackgroundColor,
            'transparent_background_color' => sprintf("%sa0", $plainBackgroundColor),
            'more_transparent_background_color' => sprintf("%s50", $plainBackgroundColor),
            'tile_ratio' => sprintf("%s%%", $tileRatioPercentage),
        ];
    }

    /**
     * @return float
     */
    public function calcTileRatioPercentage(): float
    {
        $ratioFragments = explode(':', $this->tileRatio ?: '16:9');
        $percentage = $ratioFragments[1] / $ratioFragments[0] * 100;

        return round($percentage, 3);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->export();
    }
}
