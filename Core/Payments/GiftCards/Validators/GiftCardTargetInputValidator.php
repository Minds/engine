<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Validators;

use Minds\Core\Payments\GiftCards\Types\GiftCardTarget;
use Minds\Helpers\Validation;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;

class GiftCardTargetInputValidator implements InputTypeValidatorInterface
{
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
        if (!($input instanceof GiftCardTarget)) {
            return;
        }

        if ($input->targetEmail && Validation::isValidEmail($input->targetEmail) === false) {
            throw new GraphQLException("Invalid target email", 400, null, "Validation", ['field' => 'targetInput']);
        }
    }
}
