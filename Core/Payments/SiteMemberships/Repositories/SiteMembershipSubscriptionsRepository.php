<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Repositories;

use DateTimeImmutable;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipSubscriptionFoundException;
use Minds\Core\Payments\SiteMemberships\Repositories\DTO\SiteMembershipSubscriptionDTO;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

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
        SiteMembershipSubscriptionDTO $siteMembershipSubscription
    ): bool {

        $validFrom = $siteMembershipSubscription->validFrom ?: new DateTimeImmutable('now');
        $validTo = $siteMembershipSubscription->validTo ?: $validFrom->modify('+1 ' . ($siteMembershipSubscription->siteMembership->membershipBillingPeriod === SiteMembershipBillingPeriodEnum::MONTHLY ? 'month' : 'year'));

        $stmt = $this->mysqlClientWriterHandler->insert()
            ->into(self::TABLE_NAME)
            ->set([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'user_guid' => $siteMembershipSubscription->user->getGuid(),
                'membership_tier_guid' => $siteMembershipSubscription->siteMembership->membershipGuid,
                'stripe_subscription_id' => $siteMembershipSubscription->stripeSubscriptionId,
                'valid_from' => $validFrom->format('c'),
                'valid_to' => $validTo->format('c'),
                'manual' => (int) $siteMembershipSubscription->isManual,
                'auto_renew' => $siteMembershipSubscription->isManual ? 0 : (int) ($siteMembershipSubscription->siteMembership->membershipPricingModel === SiteMembershipPricingModelEnum::RECURRING),
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
                'user_guid',
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
     * @return iterable
     * @throws ServerErrorException
     */
    public function getAllSiteMembershipSubscriptions(?int $tenantId = null): iterable
    {
        $stmt = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'id',
                'user_guid',
                'membership_tier_guid',
                'stripe_subscription_id',
                'manual',
                'auto_renew',
                'valid_from',
                'valid_to',
            ]);

        if ($tenantId !== null) {
            $stmt->where('tenant_id', Operator::EQ, $tenantId);
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
            userGuid: (int) $data['user_guid'],
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
                'user_guid',
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
     * @param string $stripeSubscriptionId
     * @return SiteMembershipSubscription
     * @throws NoSiteMembershipSubscriptionFoundException
     * @throws ServerErrorException
     */
    public function getSiteMembershipSubscriptionByStripeSubscriptionId(
        string $stripeSubscriptionId
    ): SiteMembershipSubscription {
        $stmt = $this->mysqlClientWriterHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'id',
                'user_guid',
                'membership_tier_guid',
                'stripe_subscription_id',
                'auto_renew',
                'valid_from',
                'valid_to',
            ])
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('stripe_subscription_id', Operator::EQ, new RawExp(':stripe_subscription_id'))
            ->prepare();

        try {
            $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id') ?? -1,
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);

            if ($stmt->rowCount() === 0) {
                throw new NoSiteMembershipSubscriptionFoundException();
            }

            return $this->prepareSiteMembershipSubscription(
                $stmt->fetch(PDO::FETCH_ASSOC)
            );
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
                'user_guid',
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

    /**
     * @param string $stripeSubscriptionId
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return bool
     * @throws ServerErrorException
     */
    public function renewSiteMembershipSubscription(
        string $stripeSubscriptionId,
        int $startTimestamp,
        int $endTimestamp
    ): bool {
        $stmt = $this->mysqlClientWriterHandler->update()
            ->table(self::TABLE_NAME)
            ->set([
                'valid_from' => new RawExp(':valid_from'),
                'valid_to' => new RawExp(':valid_to'),
            ])
            ->where('stripe_subscription_id', Operator::EQ, new RawExp(':stripe_subscription_id'))
            ->prepare();

        try {
            return $stmt->execute([
                'stripe_subscription_id' => $stripeSubscriptionId,
                'valid_from' => date('c', $startTimestamp),
                'valid_to' => date('c', $endTimestamp),
            ]);
        } catch (PDOException $e) {
            throw new ServerErrorException('Failed to renew site membership subscription', previous: $e);
        }
    }

    /**
     * Returns a list of site memberships that
     * @return SiteMembershipSubscription[]
     */
    public function getOutOfSyncSiteMemberships(): array
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'msms.id',
                'msms.user_guid',
                'msms.membership_tier_guid',
                'msms.stripe_subscription_id',
                'msms.auto_renew',
                'msms.manual',
                'msms.valid_from',
                'msms.valid_to',
            ])
            ->from(new RawExp(self::TABLE_NAME . ' AS msms'))
            ->join(new RawExp('minds_site_membership_tiers_group_assignments msmga'), 'msmga.membership_tier_guid', Operator::EQ, 'msms.membership_tier_guid')
            ->leftJoinRaw(new RawExp('minds_group_membership mgm'), 'mgm.user_guid = msms.user_guid AND mgm.group_guid = msmga.group_guid')
            ->where('mgm.user_guid', Operator::IS, null)
            ->where('msms.valid_to', Operator::GT, new RawExp('NOW()'))
            ->where('msms.tenant_id', Operator::EQ, $this->config->get('tenant_id'))
            ->groupBy('msms.id');

        $stmt = $query->prepare();

        $stmt->execute();

        $return = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $return[] = $this->prepareSiteMembershipSubscription($row);
        }

        return $return;
    }
}
