<?php

namespace Minds\Core\Wire\Methods;

use Minds\Helpers;

class Points implements MethodInterface
{

    private $amount;
    private $entity;
    private $id;

    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    public function setPayload($payload = [])
    {
        return $this;
    }

    public function execute()
    {
        $this->id = Helpers\Wallet::createTransaction($this->entity->owner_guid, $this->amount, $this->entity->guid, "Wire");
    }

    public function getId()
    {
        return $this->id;
    }

}
