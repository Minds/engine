<?php
declare(strict_types=1);

namespace Minds\Core\Security\ForgotPassword;

use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Security\ForgotPassword\Cache\ForgotPasswordCache;
use Minds\Core\Security\ForgotPassword\Services\ForgotPasswordService;
use Minds\Core\Email\V2\Campaigns\Recurring\ForgotPassword\ForgotPasswordEmailer;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Security\ACL;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(ForgotPasswordCache::class, function ($di) {
            return new ForgotPasswordCache(
                cache: $di->get('Cache\PsrWrapper'),
            );
        });

        $this->di->bind(ForgotPasswordService::class, function ($di) {
            return new ForgotPasswordService(
                cache: $di->get(ForgotPasswordCache::class),
                forgotPasswordEmailer: new ForgotPasswordEmailer(),
                commonSessionsManager: $di->get('Sessions\CommonSessions\Manager'),
                sessionsManager: $di->get('Sessions\Manager'),
                saveAction: new SaveAction(),
                acl: $di->get(ACL::class),
            );
        });
    }
}
