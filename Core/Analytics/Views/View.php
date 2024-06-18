<?php
/**
 * View
 * @author edgebal
 */

namespace Minds\Core\Analytics\Views;

use Minds\Traits\MagicAttributes;

/**
 * Class View
 * @package Minds\Core\Analytics\Views
 * @method View setYear(int $year)
 * @method int getYear()
 * @method View setMonth(int $month)
 * @method int getMonth()
 * @method View setDay(int $year)
 * @method int getDay()
 * @method View setUuid(string $uuid)
 * @method string getUuid()
 * @method View setEntityUrn(string $entityUrn)
 * @method string getEntityUrn()
 * @method View setOwnerGuid(string $ownerGuid)
 * @method string getOwnerGuid()
 * @method View setPageToken(string $pageToken)
 * @method string getPageToken()
 * @method View setPosition(int $position)
 * @method int getPosition()
 * @method View setPlatform(string $platform)
 * @method string getPlatform()
 * @method View setSource(string $source)
 * @method string getSource()
 * @method View setMedium(string $medium)
 * @method string getMedium()
 * @method View setCampaign(string $campaign)
 * @method string getCampaign()
 * @method View setDelta(int $delta)
 * @method int getDelta()
 * @method View setSalt(string $salt)
 * @method string|null getSalt()
 * @method View setServedByGuid(string $servedByGuid)
 * @method string|null getServedByGuid()
 * @method View setTimestamp(int $timestamp)
 * @method int getTimestamp()
 * @method View setExternal(bool $external)
 * @method bool isExternal()
 */
class View
{
    use MagicAttributes;

    /** @var int */
    protected $year;

    /** @var int */
    protected $month;

    /** @var int */
    protected $day;

    /** @var string */
    protected $uuid;

    /** @var string */
    protected $entityUrn;

    /** @var string */
    protected $ownerGuid;

    /** @var string */
    protected $pageToken;

    /** @var int */
    protected $position;

    /** @var string */
    protected $platform;

    /** @var string */
    protected $source;

    /** @var string */
    protected $medium;

    /** @var string */
    protected $campaign;

    /** @var int */
    protected $delta;

    /** @var int */
    protected $timestamp;

    private ?string $salt = null;
    private ?string $servedByGuid = null;

    protected bool $external = false;

    /**
     * @param array $clientMeta
     * @return $this
     */
    public function setClientMeta(array $clientMeta)
    {
        $this->pageToken = $clientMeta['page_token'] ?? null;
        $this->position = $clientMeta['position'] ?? null;
        $this->platform = $clientMeta['platform'] ?? null;
        $this->source = $clientMeta['source'] ?? null;
        $this->medium = $clientMeta['medium'] ?? null;
        $this->campaign = $clientMeta['campaign'] ?? null;
        $this->delta = $clientMeta['delta'] ?? null;
        $this->salt = $clientMeta['salt'] ?? null;
        $this->servedByGuid = $clientMeta['served_by_guid'] ?? null;

        return $this;
    }
}
