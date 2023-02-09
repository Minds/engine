<?php

namespace Minds\Core\Settings\Models;

use Minds\Entities\ExportableInterface;
use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method string getUserGuid()
 * @method int|null getTermsAcceptedAt()
 * @method float|null getSupermindCashMin()
 * @method float|null getSupermindOffchainTokensMin()
 */
class UserSettings implements ExportableInterface
{
    use MagicAttributes;

    private string $userGuid;
    private ?int $termsAcceptedAt = null;
    private ?float $supermindCashMin = null;
    private ?float $supermindOffchainTokensMin = null;
    private bool $plusDemonetized = false;
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
     * Whether user is demonetized from plus.
     * @return bool true if user is demonetized from plus.
     */
    public function isPlusDemonetized(): bool
    {
        return $this->plusDemonetized;
    }

    /**
     * Set whether a user is demonetized from plus.
     * @param boolean $plusDemonetized - whether a user is demonetized from plus.
     * @return self
     */
    public function setPlusDemonetized(bool $plusDemonetized): self
    {
        $this->plusDemonetized = $plusDemonetized;
        $this->markPropertyAsUpdated('plus_demonetized', $plusDemonetized);
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

        if (isset($data['plus_demonetized'])) {
            $userSettings->setPlusDemonetized($data['plus_demonetized']);
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
            'plus_demonetized' => $this->plusDemonetized,
        ];
    }
}
