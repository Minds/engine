<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Validators;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\SupermindRequestReplyType;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Responsible for validating a user claiming a live Supermind response.
 */
class SupermindLiveReplyValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors;

    public function __construct(
        private ?SupermindManager $supermindManager = null,
        private ?Logger $logger = null
    ) {
        $this->supermindManager ??= Di::_()->get("Supermind\Manager");
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Reset errors stored in class state.
     * @return void
     */
    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    /**
     * Validate a user claiming a live Supermind request.
     * @param ServerRequestInterface $request - request object to validate.
     * @return bool - true if valid, false if not.
     */
    public function validate(array|ServerRequestInterface $request): bool
    {
        $this->resetErrors();

        $loggedInUser = $request->getAttribute("_user");
        $supermindRequestId = $request->getAttribute("parameters")["guid"];

        if (!isset($supermindRequestId)) {
            $this->errors->add(
                new ValidationError(
                    "guid",
                    "You must supply a Supermind request guid"
                )
            );
            return false;
        }
        
        try {
            $supermindRequest = $this->supermindManager
                ->setUser($loggedInUser)
                ->getRequest($supermindRequestId);
        } catch (ForbiddenException $e) {
            $this->errors->add(
                new ValidationError(
                    "guid",
                    "You are not allowed to interact with this Supermind request"
                )
            );
            return false;
        } catch (Exception $e) {
            $this->logger->error($e);
            $this->errors->add(
                new ValidationError(
                    "guid",
                    "An unknown error has occurred whilst interacting with this Supermind request"
                )
            );
            return false;
        }

        if (!$supermindRequest) {
            $this->errors->add(
                new ValidationError(
                    "guid",
                    "No Supermind request was found with the given guid"
                )
            );
            return false;
        }

        if ($supermindRequest->getReplyType() !== SupermindRequestReplyType::LIVE) {
            $this->errors->add(
                new ValidationError(
                    "guid",
                    "This Supermind request cannot be accepted as a live reply"
                )
            );
        }

        if ($supermindRequest->getReceiverGuid() !== $loggedInUser->getGuid()) {
            $this->errors->add(
                new ValidationError(
                    "guid",
                    "You are not the intended recipient for this Supermind request"
                )
            );
        }

        return $this->errors->count() === 0;
    }

    /**
     * Get a list of all errors triggered after calling to validate.
     * @return ValidationErrorCollection|null - validation error collection.
     */
    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
