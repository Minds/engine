<?php
namespace Minds\Integrations\Bloomerang\Models;

class BloomerangConstituent
{
    /** @param BloomerangGroup[] $groupsDetails */
    public function __construct(
        public readonly int $id,
        public readonly int $accountNumber,
        public readonly array $groupsDetails,
        public readonly string $type,
        public readonly string $status,
        public readonly string $email,
    ) {

    }

    /**
     * Helper function to determine if a constituent is a member of a group
     */
    public function isMemberOfGroup(int $groupId): bool
    {
        foreach ($this->groupsDetails as $groupDetail) {
            if ($groupDetail->id === $groupId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Builds a 'Constituent' from array of data
     */
    public static function buildFromArray(array $data): BloomerangConstituent
    {
        return new BloomerangConstituent(
            id: $data['Id'],
            accountNumber: $data['AccountNumber'],
            groupsDetails: array_map(fn ($group) => BloomerangGroup::buildFromArray($group), $data['GroupsDetails']),
            type: $data['Type'],
            status: $data['Status'],
            email: $data['PrimaryEmail']['Value'],
        );
    }
}
