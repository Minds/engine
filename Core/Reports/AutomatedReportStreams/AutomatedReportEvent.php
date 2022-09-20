<?php
namespace Minds\Core\Reports\AutomatedReportStreams;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;

class AutomatedReportEvent implements EventInterface
{
    use TimebasedEventTrait;

    protected string $topicName;

    /**
     * Sets the topic name variable
     * @param string $topicName
     * @return self
     */
    public function setTopicName(string $topicName): self
    {
        $this->topicName = $topicName;
        return $this;
    }

    /**
     * Will return the topic name relative to the event
     * @return string
     */
    public function getTopicName(): string
    {
        return $this->topicName;
    }
}
