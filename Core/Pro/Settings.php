<?php
/**
 * Settings.
 *
 * @author edgebal
 */

namespace Minds\Core\Pro;

use JsonSerializable;
use Minds\Traits\MagicAttributes;

/**
 * Class Settings.
 *
 * @method int|string getUserGuid()
 * @method Settings   setUserGuid(int|string $userGuid)
 * @method string     getDomain()
 * @method Settings   setDomain(string $domain)
 * @method string     getTitle()
 * @method Settings   setTitle(string $title)
 * @method string     getHeadline()
 * @method Settings   setHeadline(string $headline)
 * @method string     getTextColor()
 * @method Settings   setTextColor(string $textColor)
 * @method string     getPrimaryColor()
 * @method Settings   setPrimaryColor(string $primaryColor)
 * @method string     getPlainBackgroundColor()
 * @method Settings   setPlainBackgroundColor(string $plainBackgroundColor)
 * @method string     getTileRatio()
 * @method Settings   setTileRatio(string $tileRatio)
 * @method string     getFooterText()
 * @method Settings   setFooterText(string $footerText)
 * @method array      getFooterLinks()
 * @method Settings   setFooterLinks(array $footerLinks)
 * @method array      getTagList()
 * @method Settings   setTagList(array $tagList)
 * @method string     getScheme()
 * @method Settings   setScheme(string $scheme)
 * @method string     getBackgroundImage()
 * @method Settings   setBackgroundImage(string $backgroundImage)
 * @method string     getLogoImage()
 * @method Settings   setLogoImage(string $logoImage)
 * @method array      getFeaturedContent()
 * @method Settings   setFeaturedContent(array $featuredContent)
 * @method string     getCustomHead()
 * @method Settings   setCustomHead(string $customHead)
 * @method bool       isPublished()
 * @method Settings   setPublished(bool $published)
 * @method bool       getSplash()
 * @method Settings   setSplash(bool $splash)
 * @method bool       hasCustomLogo()
 * @method Settings   setHasCustomLogo(bool $customLogo)
 * @method bool       hasCustomBackground()
 * @method Settings   setHasCustomBackground(bool $customBackground)
 * @method int        getTimeUpdated()
 * @method Settings   setTimeUpdated(int $timeUpdated)
 * @method string     getPayoutMethod()
 * @method Settings   setPayoutMethod(string $method)
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

    /** @var string */
    protected $tileRatio = '16:9';

    /** @var bool */
    protected $hasCustomBackground;

    /** @var string */
    protected $backgroundImage;

    /** @var bool */
    protected $hasCustomLogo;

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

    /** @var bool */
    protected $published;

    /** @var bool */
    protected $splash;

    /** @var int */
    protected $timeUpdated;

    /** @var string */
    protected $payoutMethod = 'usd';

    /**
     * @return string
     */
    public function getOneLineHeadline(): string
    {
        return preg_replace('/\\r?\\n+/', ' ', $this->headline);
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
            'has_custom_logo' => $this->hasCustomLogo,
            'logo_image' => $this->logoImage,
            'has_custom_background' => $this->hasCustomBackground,
            'background_image' => $this->backgroundImage,
            'featured_content' => $this->featuredContent,
            'scheme' => $this->scheme,
            'custom_head' => $this->customHead,
            'one_line_headline' => $this->getOneLineHeadline(),
            'styles' => $this->buildStyles(),
            'published' => $this->published,
            'splash' => $this->splash,
            'time_updated' => $this->timeUpdated,
            'payout_method' => $this->payoutMethod,
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
            'transparent_background_color' => sprintf('%sa0', $plainBackgroundColor),
            'more_transparent_background_color' => sprintf('%s50', $plainBackgroundColor),
            'tile_ratio' => sprintf('%s%%', $tileRatioPercentage),
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
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->export();
    }
}
