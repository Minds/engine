<?php

namespace Minds\Core\Settings\Models;

use Minds\Core\Settings\GraphQL\Types\Dismissal;
use Minds\Entities\ExportableInterface;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method string getUserGuid()
 * @method int|null getTermsAcceptedAt()
 * @method float|null getSupermindCashMin()
 * @method float|null getSupermindOffchainTokensMin()
 * @method string|null getPlusDemonetizedTimestamp()
 * @method int|null getBoostPartnerSuitability()
 */
class UserSettings implements ExportableInterface
{
    use MagicAttributes;

    private string $userGuid;
    private ?int $termsAcceptedAt = null;
    private ?float $supermindCashMin = null;
    private ?float $supermindOffchainTokensMin = null;
    private ?string $plusDemonetizedTimestamp = null;
    private int $boostPartnerSuitability = BoostPartnerSuitability::CONTROVERSIAL;
    private ?string $dismissals = null;
    private array $dirty = [];

    public function setUserGuid(string $userGuid): self
    {
        $this->userGuid = $userGuid;
        $this->markPropertyAsUpdated('user_guid', $userGuid);
        return $this;
    }

    public function setTermsAcceptedAt(int $termsAcceptedAt): self
    {
        $this->termsAcceptedAt = $termsAcceptedAt;
        $this->markPropertyAsUpdated('terms_accepted_at', $termsAcceptedAt);
        return $this;
    }

    public function setSupermindCashMin(float $supermindCashMin): self
    {
        $this->supermindCashMin = $supermindCashMin;
        $this->markPropertyAsUpdated('supermind_cash_min', $supermindCashMin);
        return $this;
    }

    public function setSupermindOffchainTokensMin(float $supermindOffchainTokensMin): self
    {
        $this->supermindOffchainTokensMin = $supermindOffchainTokensMin;
        $this->markPropertyAsUpdated('supermind_offchain_tokens_min', $supermindOffchainTokensMin);
        return $this;
    }

    /**
     * Get raw dismissals data.
     * @param boolean $asArray - whether to get data as an array or it's native string format.
     * @return array|string - Raw dismissals data in requested format.
     */
    public function getRawDismissals(bool $asArray = true): array|string
    {
        return $asArray ?
            (json_decode($this->dismissals, true) ?? []) :
            ($this->dismissals ?? '');
    }

    /**
     * Get dismissals data as iterable of Dismissal objects.
     * @return iterable - dismissals data as iterable of Dismissal objects.
     */
    public function getDismissals(): iterable
    {
        $rawDismissals = $this->getRawDismissals();
        foreach ($rawDismissals as $rawDismissal) {
            yield new Dismissal(
                userGuid: $this->userGuid,
                key: $rawDismissal['key'],
                dismissalTimestamp: $rawDismissal['dismissal_timestamp']
            );
        }
    }

    /**
     * Sets dismissals data as encoded string of JSON object.
     * @param string $dismissals - dismissals data as a JSON string to set.
     * @return self
     */
    public function setDismissalsJsonString(string $dismissals): self
    {
        $this->dismissals = $dismissals;
        $this->markPropertyAsUpdated('dismissals', $dismissals);
        return $this;
    }


    /**
     * Get whether a user has a plus demonetization timestamp.
     * @param int $plusDemonetizedTimestamp - timestamp for a users plus demonetization.
     * @return bool true if a user has a plus demonetization timestamp.
     */
    public function isPlusDemonetized(): bool
    {
        return (bool) $this->plusDemonetizedTimestamp;
    }

    /**
     * Set a users plus demonetization timestamp.
     * @param string|null $plusDemonetizedTimestamp - timestamp for a users plus demonetization.
     * @return self
     */
    public function setPlusDemonetizedTimestamp(?string $plusDemonetizedTimestamp): self
    {
        $this->plusDemonetizedTimestamp = $plusDemonetizedTimestamp;
        $this->markPropertyAsUpdated('plus_demonetized_ts', $plusDemonetizedTimestamp);
        return $this;
    }

    /**
     * Set user's preference for boost partner audience suitability.
     * @param int $boostPartnerSuitability - e.g. disabled(1), safe(2)
     * @return self
     */
    public function setBoostPartnerSuitability(?int $boostPartnerSuitability): self
    {
        $this->boostPartnerSuitability = $boostPartnerSuitability ?? BoostPartnerSuitability::CONTROVERSIAL;
        $this->markPropertyAsUpdated('boost_partner_suitability', $boostPartnerSuitability);
        return $this;
    }

    /**
     * @param User $user
     * @return self
     */
    public function withUser(User $user): self
    {
        $instance = clone $this;
        $instance->setUserGuid($user->getGuid());
        return $instance;
    }

    /**
     * @param array $data
     * @return self
     */
    public function withData(array $data): self
    {
        $userSettings = clone $this;

        if (isset($data['terms_accepted_at'])) {
            $userSettings->setTermsAcceptedAt(
                strtotime($data['terms_accepted_at'])
            );
        }

        if (isset($data['supermind_cash_min'])) {
            $userSettings->setSupermindCashMin(
                (float) $data['supermind_cash_min']
            );
        }

        if (isset($data['supermind_offchain_tokens_min'])) {
            $userSettings->setSupermindOffchainTokensMin(
                (float) $data['supermind_offchain_tokens_min']
            );
        }

        if (array_key_exists('plus_demonetized_ts', $data)) {
            $userSettings->setPlusDemonetizedTimestamp(
                $data['plus_demonetized_ts']
            );
        }

        if (array_key_exists('boost_partner_suitability', $data)) {
            $userSettings->setBoostPartnerSuitability(
                $data['boost_partner_suitability']
            );
        }

        if (array_key_exists('dismissals', $data)) {
            $userSettings->setDismissalsJsonString(
                $data['dismissals'] ?? ''
            );
        }

        return $userSettings;
    }

    private function markPropertyAsUpdated(string $propertyName, mixed $propertyValue): void
    {
        $this->dirty[$propertyName] = $propertyValue;
    }

    public function getUpdatedProperties(): array
    {
        return $this->dirty;
    }

    public function export(array $extras = []): array
    {
        return [
            'user_guid' => $this->userGuid,
            'terms_accepted_at' => $this->termsAcceptedAt,
            'supermind_cash_min' => $this->supermindCashMin,
            'supermind_offchain_tokens_min' => $this->supermindOffchainTokensMin,
            'plus_demonetized_ts' => $this->plusDemonetizedTimestamp,
            'boost_partner_suitability' => $this->boostPartnerSuitability,
            'dismissals' => $this->dismissals
        ];
    }
}
