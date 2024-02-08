<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDOException;

class SiteMembershipSubscriptionsRepository extends AbstractRepository
{
    private const TABLE_NAME = 'minds_site_membership_subscriptions';

    /**
     * @param User $user
     * @param SiteMembership $siteMembership
     * @param string $stripeSubscriptionId
     * @return bool
     * @throws ServerErrorException
     */
    public function storeSiteMembershipSubscription(
        User           $user,
        SiteMembership $siteMembership,
        string         $stripeSubscriptionId
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'user_guid' => $user->getGuid(),
                'membership_tier_guid' => $siteMembership->membershipGuid,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'valid_from' => date('c', time()),
                'valid_to' => date('c', strtotime('+1 ' . ($siteMembership->membershipBillingPeriod === SiteMembershipBillingPeriodEnum::MONTHLY ? 'month' : 'year'))),
                'auto_renew' => (int)($siteMembership->membershipPricingModel === SiteMembershipPricingModelEnum::RECURRING),
            ])
            ->prepare();

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to store site membership subscription', previous: $e);
        }
    }
}
