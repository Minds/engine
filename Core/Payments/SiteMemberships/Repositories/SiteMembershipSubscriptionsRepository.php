<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use DateTimeImmutable;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipSubscriptionFoundException;
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
        ?string        $stripeSubscriptionId = null,
        bool           $isManual = false,
        DateTimeImmutable $validFrom = null,
        DateTimeImmutable $validTo = null,
    ): bool {

        if (!$validFrom) {
            $validFrom = new DateTimeImmutable('now');
        }

        if (!$validTo) {
            $validTo = $validFrom->modify('+1 ' . ($siteMembership->membershipBillingPeriod === SiteMembershipBillingPeriodEnum::MONTHLY ? 'month' : 'year'));
        }
        
        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'user_guid' => $user->getGuid(),
                'membership_tier_guid' => $siteMembership->membershipGuid,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'valid_from' => $validFrom->format('c'),
                'valid_to' => $validTo->format('c'),
                'manual' => (int) $isManual,
                'auto_renew' => $isManual ? 0 : (int) ($siteMembership->membershipPricingModel === SiteMembershipPricingModelEnum::RECURRING),
            ])
            ->onDuplicateKeyUpdate([
                'valid_from' => $validFrom->format('c'),
                'valid_to' => $validTo->format('c'),
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
     * @return iterable<SiteMembershipSubscription>
     * @throws ServerErrorException
     */
    public function getSiteMembershipSubscriptions(
        ?User $user = null
    ): iterable {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'id',
                'membership_tier_guid',
                'stripe_subscription_id',
                'auto_renew',
                'manual',
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
            stripeSubscriptionId: $data['stripe_subscription_id'],
            autoRenew: (bool)$data['auto_renew'],
            isManual: (bool) $data['manual'],
            validFromTimestamp: strtotime($data['valid_from']),
            validToTimestamp: $data['valid_to'] ? strtotime($data['valid_to']) : null
        );
    }

    /**
     * @param int $siteMembershipSubscriptionId
     * @return SiteMembershipSubscription
     * @throws NoSiteMembershipSubscriptionFoundException
     * @throws ServerErrorException
     */
    public function getSiteMembershipSubscriptionById(int $siteMembershipSubscriptionId): SiteMembershipSubscription
    {
        $stmt = $this->mysqlClientWriterHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'id',
                'membership_tier_guid',
                'stripe_subscription_id',
                'manual',
                'auto_renew',
                'valid_from',
                'valid_to',
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('id', Operator::EQ, $siteMembershipSubscriptionId)
            ->prepare();

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new NoSiteMembershipSubscriptionFoundException();
            }

            return $this->prepareSiteMembershipSubscription($row);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to get site membership subscription', previous: $e);
        }
    }

    /**
     * @param int $membershipGuid
     * @param User|null $user
     * @return SiteMembershipSubscription
     * @throws NoSiteMembershipSubscriptionFoundException
     * @throws ServerErrorException
     */
    public function getSiteMembershipSubscriptionByMembershipGuid(
        int   $membershipGuid,
        ?User $user = null
    ): ?SiteMembershipSubscription {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'id',
                'membership_tier_guid',
                'stripe_subscription_id',
                'auto_renew',
                'manual',
                'valid_from',
                'valid_to',
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('membership_tier_guid', Operator::EQ, $membershipGuid);

        if ($user) {
            $stmt->where('user_guid', Operator::EQ, $user->getGuid());
        }

        $stmt = $stmt->prepare();

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                return null;
            }
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $this->prepareSiteMembershipSubscription($row);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to get site membership subscription', previous: $e);
        }
    }

    /**
     * @param int $siteMembershipSubscriptionId
     * @param bool $autoRenew
     * @return void
     * @throws ServerErrorException
     */
    public function setSiteMembershipSubscriptionAutoRenew(int $siteMembershipSubscriptionId, bool $autoRenew): void
    {
        $stmt = $this->mysqlClientWriterHandler->update()
            ->table(self::TABLE_NAME)
            ->set([
                'auto_renew' => (int)$autoRenew,
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id') ?? -1)
            ->where('id', Operator::EQ, $siteMembershipSubscriptionId)
            ->prepare();

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to set site membership subscription auto renew', previous: $e);
        }
    }
}
