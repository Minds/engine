<?php
namespace Minds\Core\Payments\SiteMemberships\Types;

use DateTimeImmutable;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBatchIdTypeEnum;
use Minds\Entities\ExportableInterface;

class SiteMembershipBatchUpdate implements ExportableInterface
{
    public function __construct(
        public readonly SiteMembershipBatchIdTypeEnum $idType,
        public readonly string $id,
        public readonly int $membershipGuid,
        public readonly DateTimeImmutable $validFrom,
        public readonly DateTimeImmutable $validTo,
        public bool $updatedSuccess = false,
    ) {
        
    }

    public function export(array $extras = []): array
    {
        return [
            'id_type' => $this->idType->name,
            'id' => $this->id,
            'valid_from' => $this->validFrom->format('c'),
            'valid_to' => $this->validTo->format('c'),
            'success' => $this->updatedSuccess,
        ];
    }
}
