<?php
namespace Minds\Core\Queue;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;

/**
 * Message object
 */
class Message implements EventInterface
{
    use TimebasedEventTrait;
    
    public function __construct(
        public readonly string $queueName,
        public readonly array $data = [],
        public readonly int $delaySecs = 0,
        public readonly ?int $tenantId = null,
    ) {

    }

    public function getData(): array
    {
        return $this->data;
    }
}
