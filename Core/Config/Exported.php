<?php
/**
 * Exported.
 *
 * @author edgebal
 */

namespace Minds\Core\Config;

use Minds\Core\Blockchain\Manager as BlockchainManager;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Core\I18n\Manager as I18nManager;
use Minds\Core\Navigation\Manager as NavigationManager;
use Minds\Core\Rewards\Contributions\ContributionValues;
use Minds\Core\Session;
use Minds\Core\ThirdPartyNetworks\Manager as ThirdPartyNetworksManager;
use Minds\Entities\User;

class Exported
{
    /** @var Config */
    protected $config;

    /** @var ThirdPartyNetworksManager */
    protected $thirdPartyNetworks;

    /** @var I18nManager */
    protected $i18n;

    /** @var FeaturesManager */
    protected $features;

    /**
     * Exported constructor.
     *
     * @param Config                    $config
     * @param ThirdPartyNetworksManager $thirdPartyNetworks
     * @param I18nManager               $i18n
     * @param BlockchainManager         $blockchain
     * @param FeaturesManager           $features
     */
    public function __construct(
        $config = null,
        $thirdPartyNetworks = null,
        $i18n = null,
        $blockchain = null,
        $proDomain = null,
        $features = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->thirdPartyNetworks = $thirdPartyNetworks ?: Di::_()->get('ThirdPartyNetworks\Manager');
        $this->i18n = $i18n ?: Di::_()->get('I18n\Manager');
        $this->blockchain = $blockchain ?: Di::_()->get('Blockchain\Manager');
        $this->proDomain = $proDomain ?: Di::_()->get('Pro\Domain');
        $this->features = $features ?: Di::_()->get('Features\Manager');
    }

    /**
     * @return array
     */
    public function export(): array
    {
        $context = defined('__MINDS_CONTEXT__') ? __MINDS_CONTEXT__ : 'app';

        $exported = [
            'MindsContext' => $context,
            'LoggedIn' => Session::isLoggedIn() ? true : false,
            'Admin' => Session::isAdmin() ? true : false,
            'cdn_url' => $this->config->get('cdn_url'),
            'cdn_assets_url' => $this->config->get('cdn_assets_url'),
            'site_url' => $this->config->get('site_url'),
            'cinemr_url' => $this->config->get('cinemr_url'),
            'socket_server' => $this->config->get('sockets-server-uri') ?: 'ha-socket-io-us-east-1.minds.com:3030',
            'navigation' => NavigationManager::export(),
            'thirdpartynetworks' => $this->thirdPartyNetworks->availableNetworks(),
            'language' => $this->i18n->getLanguage(),
            'languages' => $this->i18n->getLanguages(),
            'categories' => $this->config->get('categories') ?: [],
            'stripe_key' => $this->config->get('payments')['stripe']['public_key'],
            'recaptchaKey' => $this->config->get('google')['recaptcha']['site_key'],
            'max_video_length' => $this->config->get('max_video_length'),
            'max_video_file_size' => $this->config->get('max_video_file_size'),
            'features' => (object) ($this->features->export() ?: []),
            'blockchain' => (object) $this->blockchain->getPublicSettings(),
            'sale' => $this->config->get('blockchain')['sale'],
            'last_tos_update' => $this->config->get('last_tos_update') ?: time(),
            'tags' => $this->config->get('tags') ?: [],
            'plus' => $this->config->get('plus'),
            'sendwyre' => $this->config->get('sendwyre'),
            'report_reasons' => $this->config->get('report_reasons'),
            'handlers' => [
                'plus' => $this->config->get('plus')['handler'] ?? null,
                'pro' => $this->config->get('pro')['handler'] ?? null,
            ],
            'upgrades' => $this->config->get('upgrades'),
            'contribution_values' => ContributionValues::export(),
            'environment' => getenv('MINDS_ENV') ?: 'development',
            'boost_rotator_interval' => $this->config->get('boost_rotator_interval'),
            'token_exchange_rate' => $this->config->get('token_exchange_rate'),
        ];

        if (Session::isLoggedIn()) {
            /** @var User $user */
            $user = Session::getLoggedinUser();

            $exported['user'] = $user->export();
            $exported['user']['rewards'] = (bool) $user->getPhoneNumberHash();

            if ($user->isPlus()) {
                $exported['max_video_length'] = $this->config->get('max_video_length_plus');
            }

            $canHavePlusTrial = !$user->plus_expires;
            $exported['upgrades']['plus']['monthly']['can_have_trial'] = $canHavePlusTrial;
            $exported['upgrades']['plus']['yearly']['can_have_trial'] = $canHavePlusTrial;
        }

        if ($context === 'embed') {
            $exported['MindsEmbed'] = $embedded_entity ?? null;
        }

        if ($_GET['__e_cnf_token'] ?? false) {
            $exported['from_email_confirmation'] = true;
        }

        // Pro export

        if ($pro = $this->proDomain->lookup($_SERVER['HTTP_HOST'] ?? null)) {
            $exported['pro'] = $pro;
        } elseif (!$this->proDomain->isRoot($_SERVER['HTTP_HOST'] ?? null)) {
            // If not a pro site and not root then tell frontend to redirect
            $exported['redirect_to_root_on_init'] = true;
        }

        return $exported;
    }
}
