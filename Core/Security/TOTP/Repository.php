<?php
namespace Minds\Core\Security\TOTP;

use Minds\Core\Data\Cassandra;
use Cassandra\Bigint;
use Cassandra\Varint;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Security\TOTP\Manager;
use Minds\Core\Security\TOTP\TOTPSecret;
use Minds\Core\Di\Di;

/**
 * TOTP Repository
 * @package Minds\Core\Security\TOTP
 */
class Repository
{
    /** @var Cassandra\Client */
    protected $db;

    public function __construct(Cassandra\Client $db = null)
    {
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Returns a secret for given user
     * @param TOTPSecretQueryOpts $opts
     * @return TOTPSecret
     */
    public function get(TOTPSecretQueryOpts $opts): ?TOTPSecret
    {
        if (!$opts->getUserGuid()) {
            throw new \Exception("User guid must be provided");
        }

        $statement = "SELECT * from totp_secrets WHERE user_guid=?";

        $values = [ new Bigint($opts->getUserGuid()) ];

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement, $values);

        $response = $this->db->request($prepared);

        if (!$response || !$response[0]) {
            return null;
        }

        $totpSecret = new TOTPSecret();
        $totpSecret->setSecret((string)$response[0]['secret'])
            ->setUserGuid((string) $response[0]['user_guid'])
            ->setDeviceId((string)$response[0]['device_id']);

        return $totpSecret;
    }

    /**
     * @param TOTPSecret $totpSecret
     * @return bool
     */
    public function add(TOTPSecret $totpSecret): bool
    {
        if (!$totpSecret->getDeviceId()) {
            $totpSecret->setDeviceId(Manager::DEFAULT_DEVICE_ID);
        }

        $statement = "INSERT INTO totp_secrets (user_guid, secret, device_id) VALUES (?,?,?)";
        $values = [
            new Bigint($totpSecret->getUserGuid()),
            (string) $totpSecret->getSecret(),
            (string) $totpSecret->getDeviceId(),
        ];

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }

    /**
     * NOT IMPLEMENTED
     *
     * @param TOTPSecret $totpSecret
     * @return bool
     */
    public function update(TOTPSecret $totpSecret): bool
    {
        return true;
    }

    /**
     * @param TOTPSecretQueryOpts $opts
     * @return bool
     */
    public function delete(TOTPSecretQueryOpts $opts): bool
    {
        if (!$opts->getUserGuid()) {
            throw new \Exception("User guid must be provided");
        }

        $statement = "DELETE FROM totp_secrets WHERE user_guid = ?";

        $values = [
            new Bigint($opts->getUserGuid()),
        ];

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }
}
