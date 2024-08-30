<?php
declare(strict_types=1);

namespace Minds\Core\Email\V2;

use Minds\Core\Chat\Repositories\ReceiptRepository;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Email\Mailer;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Emailer;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emailer as IssuerEmailer;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantUserWelcome\TenantUserWelcomeEmailer;
use Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages\UnreadMessages;
use Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages\UnreadMessagesDispatcher;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\Email\V2\Common\TenantTemplateVariableInjector;
use Minds\Core\Email\V2\Partials\UnreadMessages\UnreadMessagesPartial;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Core\Payments\Manager as PaymentManager;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;

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

        $this->di->bind(
            TenantUserWelcomeEmailer::class,
            fn (Di $di): TenantUserWelcomeEmailer =>
                new TenantUserWelcomeEmailer(
                    new Template(),
                    new Mailer(),
                    $di->get(Config::class),
                    $di->get(TenantTemplateVariableInjector::class),
                    $di->get(SiteMembershipReaderService::class),
                    $di->get(SiteMembershipSubscriptionsService::class),
                    $di->get(FeaturedEntityService::class),
                    $di->get('Email\Manager')
                )
        );

        $this->di->bind(TenantTemplateVariableInjector::class, function (Di $di): TenantTemplateVariableInjector {
            return new TenantTemplateVariableInjector(
                $di->get(Config::class)
            );
        }, ['useFactory' => true]);

        $this->di->bind(
            UnreadMessagesDispatcher::class,
            fn (Di $di): UnreadMessagesDispatcher =>
                new UnreadMessagesDispatcher(
                    unreadMessagesEmailer: $di->get(UnreadMessages::class),
                    multiTenantBootService: $di->get(MultiTenantBootService::class),
                    multiTenantDataService: $di->get(MultiTenantDataService::class),
                    receiptRepository: $di->get(ReceiptRepository::class),
                    entitiesBuilder: $di->get(EntitiesBuilder::class),
                    logger: $di->get('Logger')
                )
        );

        $this->di->bind(
            UnreadMessagesPartial::class,
            fn (Di $di): UnreadMessagesPartial =>
                new UnreadMessagesPartial(
                    chatRoomService: $di->get(RoomService::class),
                    logger: $di->get('Logger')
                )
        );

        $this->di->bind(
            UnreadMessages::class,
            fn (Di $di): UnreadMessages =>
                new UnreadMessages(
                    new Template(),
                    new Mailer(),
                    $di->get('Email\Manager'),
                    $di->get(Config::class),
                    $di->get(TenantTemplateVariableInjector::class),
                    $di->get(UnreadMessagesPartial::class)
                )
        );
    }
}
