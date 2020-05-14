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
 * @method string getCurrency()
 * @method SupportTier setCurrency(string $currency)
 * @method string getGuid()
 * @method SupportTier setGuid(string $guid)
 * @method float getAmount()
 * @method SupportTier setAmount(float $amount)
 * @method string getName()
 * @method SupportTier setName(string $name)
 * @method string getDescription()
 * @method SupportTier setDescription(string $description)
 */
class SupportTier implements JsonSerializable
{
    use MagicAttributes;

    /** @var string */
    protected $entityGuid;

    /** @var string */
    protected $currency;

    /** @var string */
    protected $guid;

    /** @var float */
    protected $amount;

    /** @var string */
    protected $name;

    /** @var string */
    protected $description;

    /**
     * Builds URN
     * @return string|null
     */
    public function getUrn(): ?string
    {
        if ($this->entityGuid && $this->currency && $this->guid) {
            return Urn::build('support-tier', [
                $this->entityGuid,
                $this->currency,
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
            'currency' => $this->currency,
            'guid' => $this->guid,
            'amount' => (string) $this->amount,
            'name' => $this->name,
            'description' => $this->description,
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
