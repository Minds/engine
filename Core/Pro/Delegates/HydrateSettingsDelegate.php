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
use Minds\Helpers\Text;

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
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param User $user
     * @param Settings $settings
     * @return Settings
     */
    public function onGet(User $user, Settings $settings): Settings
    {
        try {
            $logoImage = $settings->getLogoGuid() ? sprintf(
                '%sfs/v1/thumbnail/%s/master',
                $this->config->get('cdn_url'),
                $settings->getLogoGuid()
            ) : $user->getIconURL('large');

            if ($logoImage) {
                $settings
                    ->setLogoImage($logoImage);
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

        try {
            if ($user->getPinnedPosts()) {
                $pinnedPosts = $this->entitiesBuilder->get(['guids' => Text::buildArray($user->getPinnedPosts())]);

                uasort($pinnedPosts, function ($a, $b) {
                    if (!$a || !$b) {
                        return 0;
                    }

                    return ($a->time_created < $b->time_created) ? 1 : -1;
                });

                $featuredContent = Text::buildArray(array_values(array_filter(array_map(function ($pinnedPost) {
                    return $pinnedPost->entity_guid ?: $pinnedPost->guid ?: null;
                }, $pinnedPosts))));

                $settings->setFeaturedContent($featuredContent);
            }
        } catch (\Exception $e) {
            error_log($e);
        }

        $settings->setPublished($user->isProPublished());
        return $settings;
    }
}
