<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\CustomerPortal\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class CustomerPortalConfigurationRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_payments_config';

    /**
     * @param string $customerPortalConfigId
     * @return bool
     * @throws ServerErrorException
     */
    public function storeCustomerPortalConfiguration(
        string $customerPortalConfigId
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'stripe_customer_portal_config_id' => new RawExp(':stripe_customer_portal_config_id'),
            ])
            ->prepare();

        try {
            return $stmt->execute(['stripe_customer_portal_config_id' => $customerPortalConfigId]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to store customer portal configuration', previous: $e);
        }
    }

    /**
     * @return string|null
     * @throws ServerErrorException
     */
    public function getCustomerPortalConfigurationId(): ?string
    {
        $stmt = $this->mysqlClientWriterHandler->select()
            ->from(self::TABLE_NAME)
            ->columns(['stripe_customer_portal_config_id'])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->prepare();

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                return null;
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['stripe_customer_portal_config_id'];
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to get customer portal configuration', previous: $e);
        }
    }
}
