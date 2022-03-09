<?php


namespace Minds\Core\Email\V2\SendLists;

use Minds\Core;
use Minds\Core\Analytics\Iterators\SignupsIterator;
use Minds\Entities\User;

class SignupsByDateSendList extends AbstractSendList implements SendListInterface
{
    /** @var SignupsIterator */
    private $signupsIterator;

    /** @var int */
    private $daysAgo;

    public function __construct($signupsIterator = null)
    {
        $this->signupsIterator = $signupsIterator ?? new SignupsIterator();
    }

    /**
     * Sets arguments that the cli has provided
     * @param array $cliOpts
     * @return self
     */
    public function setCliOpts(array $cliOpts = []): self
    {
        foreach ($cliOpts as $k => $v) {
            switch ($k) {
                case "days-ago":
                    $this->daysAgo = $v;
                    break;
            }
        }

        return $this;
    }

    /**
     * Fetch all the users who are subscribed to a certain email campaign/topic
     */
    public function getList(): iterable
    {
        $this->signupsIterator->setPeriod($this->daysAgo);
        
        foreach ($this->signupsIterator as $user) {
            yield $user;
        }
    }
}
