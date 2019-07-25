<?php
/**
 * HydrateSettingsDelegate
 * @author edgebal
 */

namespace Minds\Core\Pro\Delegates;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Pro\Settings;
use Minds\Entities\Object\Carousel;
use Minds\Entities\User;

class HydrateSettingsDelegate
{
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Config */
    protected $config;

    /**
     * HydrateSettingsDelegate constructor.
     * @param EntitiesBuilder $entitiesBuilder
     * @param Config $config
     */
    public function __construct(
        $entitiesBuilder = null,
        $config = null
    )
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param User $user
     * @param Settings $settings
     * @return Settings
     */
    public function onGet(User $user, Settings $settings)
    {
        try {
            $avatarUrl = $user->getIconURL('large');

            if ($avatarUrl) {
                $settings
                    ->setLogoImage($avatarUrl);
            }
        } catch (\Exception $e) {
            error_log($e);
        }

        try {
            $carousels = $this->entitiesBuilder->get(['subtype' => 'carousel', 'owner_guid' => (string) $user->guid]);
            $carousel = $carousels[0] ?? null;

            if ($carousel) {
                $settings
                    ->setBackgroundImage(sprintf(
                        '%sfs/v1/banners/%s/fat/%s',
                        $this->config->get('cdn_url'),
                        $carousel->guid,
                        $carousel->last_updated
                    ));
            }
        } catch (\Exception $e) {
            error_log($e);
        }

        return $settings;
    }
}
