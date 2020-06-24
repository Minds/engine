<?php
namespace Minds\Core\Wire\Paywall;

trait PaywallEntityTrait
{
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
        if (isset($this->attributes['flags']) && $this->attributes['flags']['paywall']) {
            return $this->attributes['flags']['paywall'];
        }
        return (bool) $this->paywall;
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
            return $this->wireThreshold;
        }
        return null;
    }
}
