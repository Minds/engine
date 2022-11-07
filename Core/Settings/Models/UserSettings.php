<?php

namespace Minds\Core\Settings\Models;

use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setTermsAcceptedAt(int $termsAcceptedAt)
 * @method int|null getTermsAcceptedAt()
 * @method self setSupermindCashMin(float $supermindCashMin)
 * @method float|null getSupermindCashMin()
 * @method self setSupermindOffchainTokensMin(float $supermindOffchainTokensMin)
 * @method float|null getSupermindOffchainTokensMin()
 */
class UserSettings implements ExportableInterface
{
    use MagicAttributes;

    private string $userGuid;
    private ?int $termsAcceptedAt = null;
    private ?float $supermindCashMin = null;
    private ?float $supermindOffchainTokensMin = null;

    /**
     * @param array $data
     * @return static
     * @throws UserSettingsNotFoundException
     */
    public static function fromData(array $data): self
    {
        $userSettings = new self();

        if (!isset($data['user_guid'])) {
            throw new UserSettingsNotFoundException();
        }
        $userSettings->setUserGuid($data['user_guid']);

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

        return $userSettings;
    }

    public function overrideWithData(array $data): self
    {
        if (isset($data['terms_accepted_at'])) {
            $this->setTermsAcceptedAt(
                strtotime($data['terms_accepted_at'])
            );
        }

        if (isset($data['supermind_cash_min'])) {
            $this->setSupermindCashMin(
                (float) $data['supermind_cash_min']
            );
        }

        if (isset($data['supermind_offchain_tokens_min'])) {
            $this->setSupermindOffchainTokensMin(
                (float) $data['supermind_offchain_tokens_min']
            );
        }
        return $this;
    }

    public function export(array $extras = []): array
    {
        return [
            'user_guid' => $this->userGuid,
            'terms_accepted_at' => $this->termsAcceptedAt,
            'supermind_cash_min' => $this->supermindCashMin,
            'supermind_offchain_tokens_min' => $this->supermindOffchainTokensMin,
        ];
    }
}
