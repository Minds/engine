<?php
namespace Minds\Core\Blockchain\Wallets\Onchain\UniqueOnChain;

use Minds\Core\Data\Cassandra;
use Minds\Core\Di\Di;
use Minds\Common\Repository\Response;

class Repository
{
    /** @var Cassandra\Client */
    protected $db;

    public function __construct(Cassandra\Client $db = null)
    {
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param string $address
     * @return UniqueOnChainAddress
     */
    public function get(string $address): ?UniqueOnChainAddress
    {
        $statement = "SELECT * from onchain_unique_addresses WHERE address = ?";
        $values = [$address];

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement, $values);

        $response = $this->db->request($prepared);

        if (!$response || !$response[0]) {
            return null;
        }

        $uniqueAddress = new UniqueOnChainAddress();
        $uniqueAddress->setAddress($response[0]['address'])
            ->setUserGuid((string) $response[0]['user_guid']);
  
        return $uniqueAddress;
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getList($opts): Response
    {
        $statement = "SELECT * from onchain_unique_addresses";

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement);

        $result = $this->db->request($prepared);

        $response = new Response();

        if (!$result || !$result[0]) {
            return $response;
        }

        foreach ($result as $row) {
            $uniqueAddress = new UniqueOnChainAddress();
            $uniqueAddress->setAddress($row['address'])
                ->setUserGuid((string) $row['user_guid']);

            $response[] = $uniqueAddress;
        }

        return $response;
    }

    /**
     * @param UniqueOnChainAddress $address)
     * @return bool
     */
    public function add(UniqueOnChainAddress $address): bool
    {
        $statement = "INSERT INTO onchain_unique_addresses (address, user_guid) VALUES (?,?)";
        $values = [
            $address->getAddress(),
            new \Cassandra\Bigint($address->getUserGuid()),
        ];

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }

    /**
     * @param UniqueOnChainAddress $address)
     * @return bool
     */
    public function update(UniqueOnChainAddress $address): bool
    {
        return true;
    }

    /**
     * @param UniqueOnChainAddress $address)
     * @return bool
     */
    public function delete(UniqueOnChainAddress $address): bool
    {
        $statement = "DELETE FROM onchain_unique_addresses WHERE address = ?";
        $values = [
            $address->getAddress()
        ];

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }
}
