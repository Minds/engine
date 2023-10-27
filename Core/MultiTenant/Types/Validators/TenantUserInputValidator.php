<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Validators;

use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Core\Session;
use Minds\Exceptions\StringLengthException;
use Minds\Helpers\StringLengthValidators\UsernameLengthValidator;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;

class TenantUserInputValidator implements InputTypeValidatorInterface
{
    private MultiTenantDataService $multiTenantDataService;
    private UsernameLengthValidator $usernameLengthValidator;

    public function __construct()
    {
    }
    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * @throws GraphQLException
     */
    public function validate(object $input): void
    {
        if (!($input instanceof TenantUser)) {
            return;
        }

        try {
            $this->getUsernameLengthValidator()->validate($input->username);
        } catch(StringLengthException $e) {
            throw new GraphQLException($e->getMessage(), 400, null, "Validation", ['field' => 'username']);
        }

        // Check for username collision.
        if (check_user_index_to_guid(strtolower($input->username))) {
            throw new GraphQLException('Username already exists', 400, null, "Validation", ['field' => 'username']);
        }

        $tenant = $this->getMultiTenantDataService()->getTenantFromId($input->tenantId);

        if (!$tenant) {
            throw new GraphQLException("Invalid tenant provided", 400, null, "Validation", ['field' => 'tenantId']);
        }

        if ($tenant->ownerGuid !== Session::getLoggedInUserGuid() || !Session::isAdmin()) {
            throw new GraphQLException("Logged in user is not owner of tenant", 400, null, "Validation", ['field' => 'tenantId']);
        }
    }

    private function getUsernameLengthValidator(): UsernameLengthValidator
    {
        return $this->usernameLengthValidator ??= new UsernameLengthValidator();
    }

    private function getMultiTenantDataService(): MultiTenantDataService
    {
        return $this->multiTenantDataService ??= Di::_()->get(MultiTenantDataService::class);
    }
}
