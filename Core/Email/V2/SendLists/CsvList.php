<?php


namespace Minds\Core\Email\V2\SendLists;

use Iterator;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * A precomputed list of users active in the last 30 days
 */
class CsvList extends AbstractSendList implements SendListInterface
{
    protected string $csvFileSrc;

    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
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
                case "csv-file":
                    $this->csvFileSrc = $v;
                    break;
            }
        }

        return $this;
    }

    /**
     * Fetch all the users who are subscribed to a certain email campaign/topic
     */
    public function getList(): Iterator
    {
        if (($handle = fopen($this->csvFileSrc, "r")) === false) {
            throw new ServerErrorException("Could not open file");
        }

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $guid = $data[0];
            $user = $this->entitiesBuilder->single($guid);

            if (!$user instanceof User) {
                continue;
            }

            yield $user;
        }

        fclose($handle);
    }
}
