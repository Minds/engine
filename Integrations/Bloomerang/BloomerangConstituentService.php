<?php
namespace Minds\Integrations\Bloomerang;

use DateTimeImmutable;
use Error;
use GuzzleHttp\Client;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\Payments\SiteMemberships\Repositories\DTO\SiteMembershipSubscriptionDTO;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Integrations\Bloomerang\Models\BloomerangConstituent;

class BloomerangConstituentService
{
    public function __construct(
        private Config $config,
        private Client $httpClient,
        private SiteMembershipReaderService $siteMembershipReaderService,
        private SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService,
        private Repository $repository,
    ) {
        
    }

    /**
     * Syncs a user to possible site memberships
     */
    public function syncUser(User $user): void
    {
        try {
            // If the user is not verified email, do not sync
            if (!$user->isTrusted()) {
                return;
            }
            
            // Get the constituent
            $constituent = $this->getConstituentByEmail($user->getEmail());

            // Get map of bloomerang group guids to site memberships

            foreach ($this->repository->getGroupIdToSiteMembershipGuidMap() as $kv) {
                $groupId = $kv->key;
                $siteMembershipGuid = $kv->value;
        
                if (!$constituent->isMemberOfGroup($groupId)) {
                    continue;
                }

                // The constituent must be in one the following groups to be assigned a membership
                $siteMembership = $this->siteMembershipReaderService->getSiteMembership($siteMembershipGuid);

                // Join the user to the membership
                $this->siteMembershipSubscriptionsService->addSiteMembershipSubscription(
                    new SiteMembershipSubscriptionDTO(
                        user: $user,
                        siteMembership: $siteMembership,
                        isManual: true,
                        validFrom: new DateTimeImmutable(),
                        validTo: new DateTimeImmutable('+1 year'),
                    )
                );
            }
        } catch (\Exception|Error $e) {
            // Continue
        }
    }

    protected function getConstituentByEmail(string $email): BloomerangConstituent
    {
        $response = $this->httpClient->request('GET', 'https://api.bloomerang.co/v2/constituents/search?search=' . $email, [
            'headers' => [
                'X-API-KEY' => $this->getApiKey(),
                'User-Agent' => 'Minds Networks'
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $rows = $data['Results'];

        if (count($rows) === 0) {
            throw new NotFoundException();
        }

        foreach ($rows as $row) {
            $constituent = BloomerangConstituent::buildFromArray($row);
            // Check the emails actually match, as Bloomerang search is a little fuzzy
            if (strtolower($constituent->email) === strtolower($email)) {
                return $constituent;
            }
        }
    
        throw new NotFoundException();
    }

    private function getApiKey(): string
    {
        /** @var Tenant */
        $tenant = $this->config->get('tenant');

        return $tenant->config->bloomerangApiKey;
    }

}
