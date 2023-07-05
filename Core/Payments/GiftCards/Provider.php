<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Emailer;
use Minds\Core\Payments\GiftCards\Controllers\Controller;
use Minds\Core\Payments\GiftCards\Delegates\EmailDelegate;
use Minds\Core\Payments\GiftCards\Delegates\NotificationDelegate;
use Minds\Core\Payments\V2\Manager as PaymentsManager;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(Repository::class, function (Di $di): Repository {
            return new Repository($di->get(MySQL\Client::class), $di->get('Logger'));
        }, ['factory' => true]);

        $this->di->bind(PaymentProcessor::class, function (Di $di): PaymentProcessor {
            return new PaymentProcessor(
                $di->get('Logger')
            );
        }, ['factory' => true]);

        $this->di->bind(Manager::class, function (Di $di): Manager {
            return new Manager(
                $di->get(Repository::class),
                $di->get(PaymentsManager::class),
                $di->get(PaymentProcessor::class),
                $di->get(EmailDelegate::class),
                $di->get('Logger'),
                $di->get(NotificationDelegate::class),
            );
        }, ['factory' => true]);

        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller(
                $di->get(Manager::class),
                $di->get('Logger')
            );
        });

        $this->di->bind(
            EmailDelegate::class,
            fn (Di $di): EmailDelegate =>
                new EmailDelegate(
                    $di->get(Emailer::class),
                    $di->get('EntitiesBuilder'),
                )
        );

        $this->di->bind(
            NotificationDelegate::class,
            fn (Di $di): NotificationDelegate =>
                new NotificationDelegate(
                    $di->get('EventStreams\Topics\ActionEventsTopic'),
                    $di->get('EntitiesBuilder'),
                    $di->get('Experiments\Manager'),
                    $di->get('Logger'),
                )
        );
    }
}
