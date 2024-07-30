<?php
declare(strict_types=1);

namespace Minds\Core\Channels\Delegates\Artifacts\MySQL;

use Minds\Core\Channels\Delegates\Artifacts\ArtifactsDelegateInterface;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Deletegate for the deletion of entities from MySQL.
 */
class EntityDelegate extends AbstractRepository implements ArtifactsDelegateInterface
{
    public function __construct(
        ?Client $mysqlHandler = null,
        ?Config $config = null,
        ?Logger $logger = null,
        ?Connection $mysqlClientWriterHandler = null,
        ?Connection $mysqlClientReaderHandler = null
    ) {
        parent::__construct(
            $mysqlHandler ?: Di::_()->get('Database\MySQL\Client'),
            $config ?: Di::_()->get(Config::class),
            $logger ?: Di::_()->get('Logger'),
            $mysqlClientWriterHandler,
            $mysqlClientReaderHandler
        );
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function snapshot($userGuid)
    {
        return true;
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function restore($userGuid)
    {
        return true;
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function hide($userGuid)
    {
        return true;
    }

    /**
     * @param string|int $userGuid
     * @return bool
     */
    public function delete($userGuid)
    {
        $query = $this->mysqlClientWriterHandler->delete()
            ->from('minds_entities')
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('owner_guid', Operator::EQ, new RawExp(':user_guid'));

        return $query->prepare()->execute([
            'tenant_id' => $this->config->get('tenant_id') ?? -1,
            'user_guid' => $userGuid
        ]);
    }
}
