<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipsFoundException;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Exceptions\ServerErrorException;
use PDO;
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
        } catch (Exception $e) {
            throw new ServerErrorException(
                message: 'Failed to store site membership',
                previous: $e
            );
        }
    }

    /**
     * @return iterable
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
        } catch (Exception $e) {
            throw new ServerErrorException(
                message: 'Failed to get site memberships',
                previous: $e
            );
        }
    }
}
