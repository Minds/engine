<?php
namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Repositories\TenantUsersRepository;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBatchIdTypeEnum;
use Minds\Core\Payments\SiteMemberships\Repositories\DTO\SiteMembershipSubscriptionDTO;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipBatchUpdate;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class SiteMembershipBatchService
{
    public function __construct(
        private EntitiesBuilder $entitiesBuilder,
        private OidcUserService $oidcUserService,
        private TenantUsersRepository $tenantUsersRepository,
        private Config $config,
        private SiteMembershipReaderService $readerService,
        private SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService,
        private Logger $logger,
    ) {
        
    }

    /**
     * @param SiteMembershipBatchUpdate[] $items
     */
    public function process(array $items): array
    {
        if (!$this->config->get('tenant_id')) {
            throw new ForbiddenException("Batch service is only available to tenants");
        }

        foreach ($items as $item) {

            $siteMembership = $this->readerService->getSiteMembership($item->membershipGuid);

        
            /** @var User[] */
            $users = match ($item->idType) {
                SiteMembershipBatchIdTypeEnum::GUID => [
                    $this->getUserFromGuid($item->id)
                ],
                SiteMembershipBatchIdTypeEnum::OIDC => [
                    $this->getUserFromOidcId($item->id)
                ],
                SiteMembershipBatchIdTypeEnum::EMAIL => $this->getUsersFromEmail($item->id),
            };

            foreach ($users as $user) {
                try {
                    $this->siteMembershipSubscriptionsService->addSiteMembershipSubscription(
                        new SiteMembershipSubscriptionDTO(
                            user: $user,
                            siteMembership: $siteMembership,
                            isManual: true,
                            validFrom: $item->validFrom,
                            validTo: $item->validTo,
                        )
                    );
                    $item->updatedSuccess = true;
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        return $items;
    }

    private function getUserFromGuid(int $guid): User
    {
        $user = $this->entitiesBuilder->single($guid);
    
        if (!$user instanceof User) {
            throw new NotFoundException("Could not find GUID - $guid");
        }

        return $user;
    }

    private function getUserFromOidcId(string $id): User
    {
        list($providerId, $oidcId) = explode('::', $id);
        $user = $this->oidcUserService->getUserFromSub($oidcId, $providerId);

        if (!$user instanceof User) {
            throw new NotFoundException("Could not find OIDC id - $id");
        }

        return $user;
    }

    /**
     * @return User[]
     */
    private function getUsersFromEmail(string $email): array
    {
        $userGuids = $this->tenantUsersRepository->getUserGuids(
            tenantId: $this->config->get('tenant_id'),
            email: $email,
        );

        $users = [];

        foreach ($userGuids as $userGuid) {
            try {
                $users[] = $this->getUserFromGuid($userGuid);
            } catch (NotFoundException) {
            }
        }

        return $users;
    }
}
