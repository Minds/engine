<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Validators;

use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Core\MultiTenant\Types\NetworkUser;
use Minds\Core\Session;
use Minds\Exceptions\StringLengthException;
use Minds\Helpers\StringLengthValidators\UsernameLengthValidator;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;

class NetworkUserInputValidator implements InputTypeValidatorInterface
{

    public function __construct(
        private readonly UsernameLengthValidator $usernameLengthValidator,
        private readonly MultiTenantDataService $multiTenantDataService,
    ) {
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
        if (!($input instanceof NetworkUser)) {
            return;
        }

        try {
            $this->usernameLengthValidator->validate($input->username);
        } catch(StringLengthException $e) {
            throw new GraphQLException($e->getMessage(), 400, null, "Validation", ['field' => 'username']);
        }

        // Check for username collision.
        if (check_user_index_to_guid(strtolower($input->username))) {
            throw new GraphQLException('Username already exists', 400, null, "Validation", ['field' => 'username']);
        }

        $tenant = $this->multiTenantDataService->getTenantFromId($input->tenantId);

        if (!$tenant) {
            throw new GraphQLException("Invalid tenant provided", 400, null, "Validation", ['field' => 'tenantId']);
        }

        if ($tenant->ownerGuid !== Session::getLoggedInUserGuid() || !Session::isAdmin()) {
            throw new GraphQLException("Logged in user is not owner of tenant", 400, null, "Validation", ['field' => 'tenantId']);
        }
    }
}
