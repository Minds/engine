<?php

namespace Minds\Core\Settings\Models;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setTermsAcceptedAt(int $termsAcceptedAt)
 * @method int getTermsAcceptedAt()
 */
class UserSettings implements ExportableInterface
{
    use MagicAttributes;

    private string $userGuid;
    private ?int $termsAcceptedAt = null;

    public static function fromData(array $data): self
    {
        return new UserSettings();
    }

    public function export(array $extras = []): array
    {
        return [
            'user_guid' => $this->userGuid,
            'terms_accepted_at' => $this->termsAcceptedAt
        ];
    }
}
