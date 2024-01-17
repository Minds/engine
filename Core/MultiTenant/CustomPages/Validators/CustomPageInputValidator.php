<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\CustomPages\Validators;

use Minds\Core\MultiTenant\CustomPages\Types\CustomPageInput;
use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;

/**
 * Validates input for custom pages
 * before input is passed to the controller.
 */
class CustomPageInputValidator implements InputTypeValidatorInterface
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
        if (!($input instanceof CustomPageInput)) {
            return;
        }

        try {
            // Convert the incoming integer to the enum type
            $pageTypeEnum = CustomPageTypesEnum::from($input->pageType);
        } catch (\ValueError $e) {
            throw new GraphQLException("Invalid page type provided.", 400, null, "Validation", ['field' => 'pageType']);
        }

        if (isset($input->externalLink) && mb_strlen($input->externalLink) > 2000) {
            throw new GraphQLException("External link can be at most 2000 characters", 400, null, "Validation", ['field' => 'externalLink']);
        }

        if (isset($input->content) && mb_strlen($input->content) > 65000) {
            throw new GraphQLException("Custom content can be at most 65000 characters", 400, null, "Validation", ['field' => 'content']);
        }

        // Normalize content and external link - convert empty strings to null
        $normalizedContent = $input->content !== '' ? $input->content : null;
        $normalizedExternalLink = $input->externalLink !== '' ? $input->externalLink : null;

        if ($normalizedContent !== null && $normalizedExternalLink !== null) {
            throw new GraphQLException("Only one of content or external link may have a value, not both.", 400, null, "Validation", ['fields' => ['content', 'externalLink']]);
        }

        return;
    }
}
