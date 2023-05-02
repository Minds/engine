<?php

/**
 * Exported.
 *
 * @author edgebal
 */

namespace Minds\Core\Config;

use Minds\Core\Blockchain\Manager as BlockchainManager;
use Minds\Core\Boost\Network\Rates;
use Minds\Core\Boost\V3\Enums\BoostRejectionReason;
use Minds\Core\Di\Di;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\I18n\Manager as I18nManager;
use Minds\Core\Navigation\Manager as NavigationManager;
use Minds\Core\Rewards\Contributions\ContributionValues;
use Minds\Core\Session;
use Minds\Core\Supermind\Settings\Models\Settings as SupermindSettings;
use Minds\Core\ThirdPartyNetworks\Manager as ThirdPartyNetworksManager;
use Minds\Core\Wire;
use Minds\Entities\User;

class Exported
{
    /** @var Config */
    protected $config;

    /** @var ThirdPartyNetworksManager */
    protected $thirdPartyNetworks;

    /** @var I18nManager */
    protected $i18n;

    /** @var BlockchainManager */
    protected $blockchain;

    protected $proDomain;

    /**
     * Exported constructor.
     *
     * @param Config                    $config
     * @param ThirdPartyNetworksManager $thirdPartyNetworks
     * @param I18nManager               $i18n
     * @param BlockchainManager         $blockchain
     * @param ExperimentsManager        $experimentsManager
     * @param Rates                     $boostRates
     */
    public function __construct(
        $config = null,
        $thirdPartyNetworks = null,
        $i18n = null,
        $blockchain = null,
        $proDomain = null,
        private ?ExperimentsManager $experimentsManager = null,
        private ?Rates $boostRates = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->thirdPartyNetworks = $thirdPartyNetworks ?: Di::_()->get('ThirdPartyNetworks\Manager');
        $this->i18n = $i18n ?: Di::_()->get('I18n\Manager');
        $this->blockchain = $blockchain ?: Di::_()->get('Blockchain\Manager');
        $this->proDomain = $proDomain ?: Di::_()->get('Pro\Domain');
        $this->experimentsManager = $experimentsManager ?? Di::_()->get('Experiments\Manager');
        $this->boostRates ??= Di::_()->get('Boost\Network\Rates');
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
            'socket_server' => $this->config->get('sockets')['server_uri'] ?: 'ha-socket-io-us-east-1.minds.com:3030',
            'navigation' => NavigationManager::export(),
            'language' => $this->i18n->getLanguage(),
            'languages' => $this->i18n->getLanguages(),
            'categories' => $this->config->get('categories') ?: [],
            'stripe_key' => $this->config->get('payments')['stripe']['public_key'] ?? '',
            'max_video_length' => $this->config->get('max_video_length'),
            'max_video_length_plus' => $this->config->get('max_video_length_plus'),
            'max_video_file_size' => $this->config->get('max_video_file_size'),
            'max_name_length' => $this->config->get('max_name_length') ?? 50,
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
            'matrix' => [
                'chat_url' => $this->config->get('matrix')['chat_url'] ?? null,
            ],
            'statuspage_io' => [
                'url' => $this->config->get('statuspage_io')['url'] ?? null,
            ],
            'experiments' => [], // TODO: remove when clients support growthbook features
            'growthbook' =>  $this->experimentsManager
                ->setUser(Session::getLoggedinUser())
                ->getExportableConfig(),
            'twitter' => [
                'min_followers_for_sync' => $this->config->get('twitter')['min_followers_for_sync'] ?? 25000,
            ],
            'vapid_key' => $this->config->get("webpush_vapid_details")['public_key'],
            'boost_rates' => [
                'cash' => $this->boostRates->getUsdRate(),
                'tokens' => $this->boostRates->getTokenRate()
            ],
            'chatwoot' => [
                'website_token' => $this->config->get('chatwoot')['website_token'],
                'base_url' => $this->config->get('chatwoot')['base_url']
            ]
        ];

        if (Session::isLoggedIn()) {
            /** @var User $user */
            $user = Session::getLoggedinUser();

            $exported['user'] = $user->export();
            $exported['user']['rewards'] = (bool) $user->getPhoneNumberHash();

            if ($user->isPlus()) {
                $exported['max_video_length'] = $this->config->get('max_video_length_plus');
            }

            $canHavePlusTrial = !$user->plus_expires || $user->plus_expires <= strtotime(Wire\Manager::TRIAL_THRESHOLD_DAYS . ' days ago');
            $exported['upgrades']['plus']['monthly']['can_have_trial'] = $canHavePlusTrial;
            $exported['upgrades']['plus']['yearly']['can_have_trial'] = $canHavePlusTrial;
        }

        if ($context === 'embed') {
            $exported['MindsEmbed'] = null;
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

        $defaultSupermindSettings = new SupermindSettings();
        $exported['supermind'] = [
            'min_thresholds' => [
                'min_cash' => $defaultSupermindSettings->getMinCash(),
                'min_offchain_tokens' => $defaultSupermindSettings->getMinOffchainTokens()
            ]
        ];

        $boost = $this->config->get('boost');
        unset($boost['offchain_wallet_guid']);
        $exported['boost'] = $boost;
        $exported['boost']['rejection_reasons'] = BoostRejectionReason::rejectionReasonsWithLabels();

        return $exported;
    }
}
