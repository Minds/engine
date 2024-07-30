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
use Selective\Database\DeleteQuery;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Deletegate for the deletion of subscribers and subscriptions, from MySQL.
 */
class FriendsDelegate extends AbstractRepository implements ArtifactsDelegateInterface
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
            ->from('friends')
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where(function (DeleteQuery $query) {
                $query->where('user_guid', Operator::EQ, new RawExp(':user_guid'));
                $query->orWhere('friend_guid', Operator::EQ, new RawExp(':friend_guid'));
            });

        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->config->get('tenant_id'),
            'user_guid' => $userGuid,
            'friend_guid' => $userGuid
        ]);
    }
}
