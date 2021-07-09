<?php
namespace Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Core\Data\Cassandra;
use Minds\Core\Di\Di;
use Minds\Common\Repository\Response;

class Repository
{
    /** @var Cassandra\Client */
    protected $db;

    /** @var Cassandra\Scroll */
    protected $scroll;

    public function __construct(Cassandra\Client $db = null, Cassandra\Scroll $scroll = null)
    {
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
        $this->scroll = $scroll ?? Di::_()->get('Database\Cassandra\Cql\Scroll');
    }

    /**
     * @param string $address
     * @return UniqueOnChainAddress
     */
    public function get(string $address): ?UniqueOnChainAddress
    {
        $statement = "SELECT * from onchain_unique_addresses WHERE address = ?";
        $values = [ strtolower($address) ];

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
     * @return iterable<UniqueOnChainAddress>
     */
    public function getList($opts): iterable
    {
        $statement = "SELECT * from onchain_unique_addresses";

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement);


        foreach ($this->scroll->request($prepared) as $row) {
            $uniqueAddress = new UniqueOnChainAddress();
            $uniqueAddress->setAddress($row['address'])
                ->setUserGuid((string) $row['user_guid']);

            yield $uniqueAddress;
        }
    }

    /**
     * @param UniqueOnChainAddress $address)
     * @return bool
     */
    public function add(UniqueOnChainAddress $address): bool
    {
        $statement = "INSERT INTO onchain_unique_addresses (address, user_guid) VALUES (?,?)";
        $values = [
            strtolower($address->getAddress()),
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
            strtolower($address->getAddress())
        ];

        $prepared = new Cassandra\Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }
}
