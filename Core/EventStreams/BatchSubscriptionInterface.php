<?php

namespace Minds\Core\EventStreams;

interface BatchSubscriptionInterface extends SubscriptionInterface
{
    public function consumeBatch(array $messages): bool;
}
