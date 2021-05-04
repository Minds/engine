<?php
namespace Minds\Core\Notifications;

use Minds\Core\Data\Cassandra;
use Cassandra\Bigint;
use Cassandra\Varint;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Notifications\Manager;
use Minds\Core\Notifications\Notification;
use Minds\Core\Di\Di;

/**
 * Notifications Repository
 * @package Minds\Core\Notifications
 */
class Repository
{
    /** @var Cassandra\Client */
    protected $db;

    public function __construct(Cassandra\Client $db = null)
    {
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
    }

    // ojm example
    // /**
    //  * Returns a secret for given user
    //  * @param TOTPSecretQueryOpts $opts
    //  * @return TOTPSecret
    //  */
    // public function get(TOTPSecretQueryOpts $opts): ?TOTPSecret
    // {
    //     if (!$opts->getUserGuid()) {
    //         throw new \Exception("User guid must be provided");
    //     }

    //     $statement = "SELECT * from totp_secrets WHERE user_guid=?";

    //     $values = [ new Bigint($opts->getUserGuid()) ];

    //     $prepared = new Cassandra\Prepared\Custom();
    //     $prepared->query($statement, $values);

    //     $response = $this->db->request($prepared);

    //     if (!$response || !$response[0]) {
    //         return null;
    //     }

    //     $totpSecret = new TOTPSecret();
    //     $totpSecret->setSecret((string)$response[0]['secret'])
    //         ->setUserGuid((string) $response[0]['user_guid'])
    //         ->setDeviceId((string) $response[0]['device_id'])
    //         ->setrecoveryHash((string) $response[0]['recovery_hash']);

    //     return $totpSecret;
    // }
}
