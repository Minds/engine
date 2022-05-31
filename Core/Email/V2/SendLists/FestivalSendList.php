<?php


namespace Minds\Core\Email\V2\SendLists;

use Minds\Core\Data\Cassandra;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;

class FestivalSendList extends AbstractSendList implements SendListInterface
{
    public function __construct(
        private ?Cassandra\Scroll $scroll = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->scroll ??= Di::_()->get('Database\Cassandra\Cql\Scroll');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Sets arguments that the cli has provided
     * @param array $cliOpts
     * @return self
     */
    public function setCliOpts(array $cliOpts = []): self
    {
        return $this;
    }

    /**
     * Fetch all the users who are subscribed to a certain email campaign/topic
     */
    public function getList(): iterable
    {
        $statement = "SELECT column1 FROM entities_by_time WHERE key='festival-email-list'";
        $values = [ ];

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement, $values);

        foreach ($this->scroll->request($prepared) as $row) {
            /** @var User */
            $user = $this->entitiesBuilder->single($row['column1']);

            if (!$user instanceof User) {
                continue;
            }

            yield $user;
        }
    }
}
