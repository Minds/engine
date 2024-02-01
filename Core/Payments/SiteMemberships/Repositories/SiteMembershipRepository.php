<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipsFoundException;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class SiteMembershipRepository extends AbstractRepository
{
    /**
     * @param SiteMembership $siteMembership
     * @param string $stripeProductId
     * @return bool
     * @throws ServerErrorException
     */
    public function storeSiteMembership(
        SiteMembership $siteMembership,
        string         $stripeProductId
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into('minds_site_membership_tiers')
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'membership_tier_guid' => $siteMembership->membershipGuid,
                'stripe_product_id' => new RawExp(':stripe_product_id'),
                'price_in_cents' => $siteMembership->membershipPriceInCents,
            ])
            ->prepare();

        try {
            return $stmt->execute([
                'stripe_product_id' => $stripeProductId,
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to store site membership',
                previous: $e
            );
        }
    }

    /**
     * @return iterable
     * @throws NoSiteMembershipsFoundException
     * @throws ServerErrorException
     */
    public function getSiteMemberships(): iterable
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from('minds_site_membership_tiers')
            ->columns([
                'membership_tier_guid',
                'stripe_product_id',
                'price_in_cents',
                'archived',
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->prepare();

        try {
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new NoSiteMembershipsFoundException();
            }
            $stmt->setFetchMode(PDO::FETCH_ASSOC);

            return $stmt->getIterator();
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to get site memberships',
                previous: $e
            );
        }
    }

    /**
     * @param int $siteMembershipGuid
     * @return bool
     * @throws ServerErrorException
     */
    public function archiveSiteMembership(int $siteMembershipGuid): bool
    {
        $stmt = $this->mysqlClientWriterHandler->update()
            ->table('minds_site_membership_tiers')
            ->set([
                'archived' => true,
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('membership_tier_guid', Operator::EQ, $siteMembershipGuid)
            ->prepare();

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to archive site membership',
                previous: $e
            );
        }
    }

    /**
     * @return int
     * @throws ServerErrorException
     */
    public function getTotalSiteMemberships(): int
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from('minds_site_membership_tiers')
            ->columns([
                'totalSiteMemberships' => new RawExp('COUNT(membership_tier_guid)'),
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->prepare();

        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['totalSiteMemberships'];
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to get total site memberships',
                previous: $e
            );
        }
    }

    /**
     * @param int $siteMembershipGuid
     * @return array
     * @throws NoSiteMembershipFoundException
     * @throws ServerErrorException
     */
    public function getSiteMembership(int $siteMembershipGuid): array
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from('minds_site_membership_tiers')
            ->columns([
                'membership_tier_guid',
                'stripe_product_id',
                'price_in_cents',
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('membership_tier_guid', Operator::EQ, $siteMembershipGuid)
            ->prepare();

        try {
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new NoSiteMembershipFoundException();
            }

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new ServerErrorException(
                message: 'Failed to get site memberships',
                previous: $e
            );
        }
    }
}
