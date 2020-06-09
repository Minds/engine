<?php
namespace Minds\Core\Wire\SupportTiers;

use JsonSerializable;
use Minds\Helpers\Urn;
use Minds\Traits\MagicAttributes;

/**
 * Support Tier Entity
 * @package Minds\Core\Wire\SupportTiers
 * @method string getEntityGuid()
 * @method SupportTier setEntityGuid(string $entityGuid)
 * @method string getGuid()
 * @method SupportTier setGuid(string $guid)
 * @method bool isPublic()
 * @method SupportTier setPublic(bool $public)
 * @method string getName()
 * @method SupportTier setName(string $name)
 * @method string getDescription()
 * @method SupportTier setDescription(string $description)
 * @method bool hasUsd()
 * @method SupportTier setHasUsd(bool $hasUsd)
 * @method float getUsd()
 * @method SupportTier setUsd(float $usd)
 * @method float getTokens()
 * @method SupportTier setTokens(float $tokens)
 * @method bool hasTokens()
 * @method SupportTier setHasTokens(bool $hasTokens)
 */
class SupportTier implements JsonSerializable
{
    use MagicAttributes;

    /** @var string */
    protected $entityGuid;

    /** @var string */
    protected $guid;

    /** @var bool */
    protected $public;

    /** @var string */
    protected $name;

    /** @var string */
    protected $description;

    /** @var bool */
    protected $hasUsd;

    /** @var float */
    protected $usd;

    /** @var bool */
    protected $hasTokens;

    /** @var float */
    protected $tokens;

    /**
     * Builds URN
     * @return string|null
     */
    public function getUrn(): ?string
    {
        if ($this->entityGuid && $this->guid) {
            return Urn::build('support-tier', [
                $this->entityGuid,
                $this->guid,
            ]);
        }

        return null;
    }

    /**
     * Exports the tier into an associative array
     * @return array
     */
    public function export(): array
    {
        return [
            'urn' => $this->getUrn(),
            'entity_guid' => $this->entityGuid,
            'guid' => $this->guid,
            'public' => $this->public,
            'name' => $this->name,
            'description' => $this->description,
            'has_usd' => $this->hasUsd,
            'usd' => (string) $this->usd,
            'has_tokens' => $this->hasTokens,
            'tokens' => (string) $this->tokens,
        ];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->export();
    }
}
