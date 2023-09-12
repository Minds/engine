<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Emailer;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emailer as IssuerEmailer;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Payments\Manager as PaymentManager;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            Emailer::class,
            fn (Di $di): Emailer =>
                new Emailer(
                    new Template(),
                    new Mailer(),
                    $di->get('EntitiesBuilder'),
                    $di->get('Config'),
                    $di->get('Logger'),
                    $di->get('Email\Manager')
                )
        );

        $this->di->bind(
            IssuerEmailer::class,
            fn (Di $di): IssuerEmailer =>
                new IssuerEmailer(
                    new Template(),
                    new Mailer(),
                    $di->get(PaymentManager::class),
                    $di->get('EntitiesBuilder'),
                    $di->get('Config'),
                    $di->get('Logger'),
                    $di->get('Email\Manager')
                )
        );
    }
}
