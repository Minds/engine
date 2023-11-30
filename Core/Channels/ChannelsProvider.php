<?php
/**
 * Provider
 * @author edgebal
 */

namespace Minds\Core\Channels;

use Minds\Core\Config\Config;
use Minds\Core\Di\Provider;
use Minds\Core\Entities\Actions\Save as SaveAction;

class ChannelsProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Channels\Manager', function ($di) {
            return new Manager();
        });

        $this->di->bind('Channels\Ban', function ($di) {
            return new Ban();
        });

        $this->di->bind('Channels\AvatarService', function ($di) {
            return new AvatarService();
        });

        $this->di->bind(BannerService::class, function ($di) {
            return new BannerService(
                imagickManager: $di->get('Media\Imagick\Manager'),
                saveAction: new SaveAction(),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            );
        });
    }
}
