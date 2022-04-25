<?php
namespace Minds\Core\DID\Keypairs;

use Cassandra\Bigint;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;

class Repository
{
    public function __construct(
        protected ?Client $cql = null
    ) {
        $this->cql ??= Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param DIDKeypair $keypair
     * @return bool
     */
    public function add(DIDKeypair $keypair): bool
    {
        $statement = "INSERT INTO did_keypairs (user_guid, keypair) VALUES (?,?)";
        $values = [
            new Bigint($keypair->getUserGuid()),
            base64_encode($keypair->getKeypair())
        ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        return (bool) $this->cql->request($prepared);
    }

    /**
     * @param string $userGuid
     * @return DIDKeypair|null
     */
    public function get(string $userGuid): ?DIDKeypair
    {
        $statement = "SELECT * FROM did_keypairs WHERE user_guid = ?";
        $values = [ new Bigint($userGuid) ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        $result = $this->cql->request($prepared);

        if (!$result || !isset($result[0])) {
            return null;
        }

        $keypair = new DIDKeypair();
        return $keypair->setUserGuid($userGuid)
            ->setKeypair(base64_decode($result[0]['keypair'], true));
    }
}
