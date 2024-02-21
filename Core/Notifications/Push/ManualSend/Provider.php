<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\Push\ManualSend;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Notifications\Push\ManualSend\Controllers\ManualSendController;
use Minds\Core\Notifications\Push\ManualSend\Interfaces\ManualSendControllerInterface;
use Minds\Core\Notifications\Push\ManualSend\Interfaces\ManualSendPayloadValidatorInterface;
use Minds\Core\Notifications\Push\ManualSend\Interfaces\ManualSendServiceInterface;
use Minds\Core\Notifications\Push\ManualSend\Services\ManualSendService;
use Minds\Core\Notifications\Push\ManualSend\Validators\ManualSendPayloadValidator;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(ManualSendController::class, function (Di $di): ManualSendControllerInterface {
            return new ManualSendController(
                $di->get(ManualSendService::class),
                $di->get(ManualSendPayloadValidator::class)
            );
        });

        $this->di->bind(ManualSendService::class, function (Di $di): ManualSendServiceInterface {
            return new ManualSendService(
                $di->get(FcmService::class),
                $di->get(ApnsService::class),
                $di->get('Logger')
            );
        });

        $this->di->bind(ManualSendPayloadValidator::class, function (Di $di): ManualSendPayloadValidatorInterface {
            return new ManualSendPayloadValidator();
        });
    }
}
