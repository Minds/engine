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
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
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
            $logoImage = $settings->hasCustomLogo() ? sprintf(
                '%sfs/v1/pro/%s/logo/%s',
                $this->config->get('cdn_url'),
                $settings->getUserGuid(),
                $settings->getTimeUpdated()
            ) : $user->getIconURL('large');

            if ($logoImage) {
                $settings
                    ->setLogoImage($logoImage);
            }
        } catch (\Exception $e) {
            error_log($e);
        }

        try {
            $backgroundImage = null;

            if ($settings->hasCustomBackground()) {
                $backgroundImage = sprintf(
                    '%sfs/v1/pro/%s/background/%s',
                    $this->config->get('cdn_url'),
                    $settings->getUserGuid(),
                    $settings->getTimeUpdated()
                );
            }

            if ($backgroundImage) {
                $settings
                    ->setBackgroundImage($backgroundImage);
            }
        } catch (\Exception $e) {
            error_log($e);
        }

        try {
            if ($user->getPinnedPosts()) {
                $pinnedPosts = $this->entitiesBuilder->get(['guids' => Text::buildArray($user->getPinnedPosts())]);

                if (!$pinnedPosts) {
                    throw new UserErrorException("No pinned posts");
                }

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
