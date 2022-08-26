<?php

namespace Minds\Core\Rewards\Restrictions\Blockchain;

use Minds\Exceptions\UserErrorException;

/**
 * Object holding data of a Restriction on an address.
 */
class Restriction
{
    /** @var array ALLOWED_REASONS - permitted reasons */
    const ALLOWED_REASONS = ['ofac', 'custom'];

    /** @var array ALLOWED_NETWORKS - permitted networks */
    const ALLOWED_NETWORKS = ['ethereum'];

    /** @var string $address - restricted address */
    private string $address;

    /** @var string $reason - reason for restriction */
    private string $reason;

    /** @var string $network - network of restriction */
    private string $network;

    /** @var string $timeAdded - timestamp of when restriction was added */
    private string $timeAdded;

    /**
     * Get address.
     * @return string address.
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Set address.
     * @param string $address - address to set.
     * @return self
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Get reason.
     * @return string reason.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Set reason.
     * @param string $reason - reason to set.
     * @return self
     */
    public function setReason(string $reason): self
    {
        if (!in_array($reason, self::ALLOWED_REASONS, true)) {
            throw new UserErrorException('Invalid reason provided');
        }
        $this->reason = $reason;
        return $this;
    }

    /**
     * Get network.
     * @return string network.
     */
    public function getNetwork(): string
    {
        return $this->network;
    }

    /**
     * Set time added.
     * @param string $timeAdded - time added.
     * @return self
     */
    public function setTimeAdded(string $timeAdded): self
    {
        $this->timeAdded = $timeAdded;
        return $this;
    }

    /**
     * Get time added as string.
     * @return string time added.
     */
    public function getTimeAdded(): string
    {
        return $this->timeAdded ?? 0;
    }

    /**
     * Set network.
     * @param string $network - network value to set.
     * @return self
     */
    public function setNetwork(string $network): self
    {
        if (!in_array($network, self::ALLOWED_NETWORKS, true)) {
            throw new UserErrorException('Invalid network provided');
        }
        $this->network = $network;
        return $this;
    }

    /**
     * Called when converting the object to a string.
     * @return string - object information as string.
     */
    public function __toString(): string
    {
        return "Found: address: {$this->getAddress()},\tnetwork: {$this->getNetwork()},\treason: {$this->getReason()}\t time_added: {$this->getTimeAdded()}";
    }
}
