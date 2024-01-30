<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Exceptions\ServerErrorException;
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
    ): bool
    {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into('minds_site_membership_tier')
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
}
