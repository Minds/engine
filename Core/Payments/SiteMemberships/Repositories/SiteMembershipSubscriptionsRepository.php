<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;

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
    ): bool
    {
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

    /**
     * @param User|null $user
     * @return iterable
     * @throws ServerErrorException
     */
    public function getSiteMembershipSubscriptions(
        ?User $user = null
    ): iterable
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'id',
                'membership_tier_guid',
                'auto_renew',
                'valid_from',
                'valid_to',
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->whereRaw('(valid_to IS NULL OR valid_to > NOW())');

        if ($user) {
            $stmt->where('user_guid', Operator::EQ, $user->getGuid());
        }
        $stmt = $stmt->prepare();

        try {
            $stmt->execute();

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                yield $this->prepareSiteMembershipSubscription($row);
            }
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to get site membership subscriptions', previous: $e);
        }
    }

    /**
     * @param array $data
     * @return SiteMembershipSubscription
     */
    private function prepareSiteMembershipSubscription(array $data): SiteMembershipSubscription
    {
        return new SiteMembershipSubscription(
            membershipSubscriptionId: (int)$data['id'],
            membershipGuid: (int)$data['membership_tier_guid'],
            autoRenew: (bool)$data['auto_renew'],
            validFromTimestamp: strtotime($data['valid_from']),
            validToTimestamp: $data['valid_to'] ? strtotime($data['valid_to']) : null
        );
    }
}
