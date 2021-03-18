<?php
namespace Minds\Core\Wire\Paywall;

trait PaywallEntityTrait
{
    /** @var bool */
    protected $paywallUnlocked = false;

    /**
     * Set the entity as being paywalled
     * @param bool $paywalled
     * @return self
     */
    public function setPayWall(bool $paywalled): self
    {
        $this->paywall = $paywalled;
        return $this;
    }

    /**
     * Return if the entity is paywalled
     * @return bool
     */
    public function isPayWall(): bool
    {
        if (property_exists($this, 'attributes') && isset($this->attributes['flags']) && ($this->attributes['flags']['paywall'] ?? false)) {
            return $this->attributes['flags']['paywall'];
        }
        return (bool) $this->paywall;
    }

    /**
     * Set the paywall to be unlocked
     * @param bool $unlocked
     * @return self
     */
    public function setPayWallUnlocked(bool $unlocked): self
    {
        $this->paywallUnlocked = $unlocked;
        return $this;
    }

    /**
     * Return if the entity is unlocked
     * @return bool
     */
    public function isPayWallUnlocked(): bool
    {
        return $this->paywallUnlocked;
    }

    /**
     * Set the paywall threshold data (or support tier for v2)
     * @param array $wireThreshold
     * @return self
     */
    public function setWireThreshold($wireThreshold = []): self
    {
        $this->wire_threshold = $wireThreshold;
        return $this;
    }

    /**
     * Returns the paywall threshold data
     * @return array
     */
    public function getWireThreshold(): ?array
    {
        $wireThreshold = $this->wire_threshold;
        if (is_string($wireThreshold)) {
            $wireThreshold = json_decode($wireThreshold, true);
        }
        if (is_array($wireThreshold)) {
            return $wireThreshold;
        }
        return null;
    }
}
