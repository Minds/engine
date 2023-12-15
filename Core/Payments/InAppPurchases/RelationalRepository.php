<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases;

use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\InAppPurchases\Enums\InAppPurchaseTypeEnum;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Exceptions\ServerErrorException;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class RelationalRepository extends AbstractRepository
{
    public function __construct(
        Client $mysqlHandler,
        Logger $logger,
        private readonly EntitiesBuilder $entitiesBuilder,
    ) {
        parent::__construct($mysqlHandler, $logger);
    }

    private const TABLE_NAME = 'minds_in_app_purchases';

    /**
     * @throws ServerErrorException
     * @throws Exception
     */
    public function storeInAppPurchaseTransaction(string $transactionId, InAppPurchase $inAppPurchase): bool
    {
        $statement = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'transaction_id' => new RawExp(':transaction_id'),
                'user_guid' => $inAppPurchase->user->getGuid(),
                'product_id' => new RawExp(':product_id'),
                'purchase_type' => $inAppPurchase->purchaseType->value,
                'purchase_timestamp' => date('c', $inAppPurchase->transactionTimestamp / 1000)
            ])
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'transaction_id' => $transactionId,
            'product_id' => $inAppPurchase->productId ?: $inAppPurchase->subscriptionId
        ]);

        if ($statement->execute() === false) {
            throw new Exception('Failed to execute statement');
        }
        return true;
    }

    /**
     * @param string $transactionId
     * @return InAppPurchase|null
     * @throws ServerErrorException
     * @throws Exception
     */
    public function getInAppPurchaseTransaction(string $transactionId): ?InAppPurchase
    {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->where('transaction_id', Operator::EQ, new RawExp(':transaction_id'))
            ->prepare();
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'transaction_id' => $transactionId
        ]);

        if ($statement->execute() === false) {
            throw new Exception('Failed to execute statement');
        }

        if ($statement->rowCount() === 0) {
            return null;
        }

        $entry = $statement->fetch(PDO::FETCH_ASSOC);
        return new InAppPurchase(
            productId: $entry['product_id'],
            transactionId: $entry['transaction_id'],
            user: $this->entitiesBuilder->single($entry['user_guid']),
            transactionTimestamp: $entry['purchase_timestamp'] ? strtotime($entry['purchase_timestamp']) : null,
            purchaseType: InAppPurchaseTypeEnum::from((int) $entry['purchase_type'])
        );
    }
}
