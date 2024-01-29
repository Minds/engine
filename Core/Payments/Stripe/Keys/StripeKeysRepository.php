<?php
namespace Minds\Core\Payments\Stripe\Keys;

use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class StripeKeysRepository extends AbstractRepository
{
    const TABLE_NAME = 'minds_stripe_keys';

    /**
     * Saves the keys to the database
     */
    public function setKeys(string $pubKey, string $secKeyCipherText): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->getTenantId(),
                'pub_key' => new RawExp(':pub_key'),
                'sec_key_cipher_text' => new RawExp(':sec_key_cipher_text'),
            ])
            ->onDuplicateKeyUpdate([
                'pub_key' => new RawExp(':pub_key'),
                'sec_key_cipher_text' => new RawExp(':sec_key_cipher_text'),
                'updated_timestamp' => date('c', time())
            ]);

        $stmt = $query->prepare();

        return $stmt->execute([
            'pub_key' => $pubKey,
            'sec_key_cipher_text' => $secKeyCipherText,
        ]);
    }

    /**
     * Returns the keys, 1st array item is the pub key, the 2nd is the secret cipher text
     * @return string[]
     */
    public function getKeys(): ?array
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'pub_key',
                'sec_key_cipher_text'
            ])
            ->where('tenant_id', Operator::EQ, $this->getTenantId());

        $stmt = $query->prepare();

        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return null;
        }

        return array_values($stmt->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * Returns the tenant id
     * -1 will be the host site
     */
    private function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
