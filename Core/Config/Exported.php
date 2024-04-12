<?php

/**
 * Exported.
 *
 * @author edgebal
 */

namespace Minds\Core\Config;

use Exception;
use Minds\Core\Analytics\PostHog\PostHogConfig;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Blockchain\Manager as BlockchainManager;
use Minds\Core\Boost\V3\Enums\BoostRejectionReason;
use Minds\Core\Chat\Services\ReceiptService;
use Minds\Core\Di\Di;
use Minds\Core\Experiments\LegacyGrowthBook;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\I18n\Manager as I18nManager;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Rewards\Contributions\ContributionValues;
use Minds\Core\Security\Rbac\Services\RolesService;
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
     * @param Config $config
     * @param ThirdPartyNetworksManager $thirdPartyNetworks
     * @param I18nManager $i18n
     * @param BlockchainManager $blockchain
     * @param ExperimentsManager $experimentsManager
     */
    public function __construct(
        $config = null,
        $thirdPartyNetworks = null,
        $i18n = null,
        $blockchain = null,
        private ?ExperimentsManager $experimentsManager = null,
        private ?RolesService $rolesService = null,
        private ?SiteMembershipRepository $siteMembershipRepository = null,
        private ?ReceiptService $chatReceiptsService = null,
        private ?PostHogConfig $postHogConfig = null,
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->thirdPartyNetworks = $thirdPartyNetworks ?: Di::_()->get('ThirdPartyNetworks\Manager');
        $this->i18n = $i18n ?: Di::_()->get('I18n\Manager');
        $this->blockchain = $blockchain ?: Di::_()->get('Blockchain\Manager');
        $this->experimentsManager = $experimentsManager ?? Di::_()->get('Experiments\Manager');
        $this->rolesService ??= Di::_()->get(RolesService::class);
        $this->siteMembershipRepository ??= Di::_()->get(SiteMembershipRepository::class);
        $this->chatReceiptsService ??= Di::_()->get(ReceiptService::class);
        $this->postHogConfig ??= Di::_()->get(PostHogConfig::class);
    }

    /**
     * @return array
     */
    public function export(): array
    {
        $context = defined('__MINDS_CONTEXT__') ? __MINDS_CONTEXT__ : 'app';

        $exported = [
            'LoggedIn' => Session::isLoggedIn() ? true : false,
            'Admin' => Session::isAdmin() ? true : false,
            'cdn_url' => $this->config->get('cdn_url'),
            'cdn_assets_url' => $this->config->get('cdn_assets_url'),
            'site_url' => $this->config->get('site_url'),
            'site_name' => $this->config->get('site_name'),
            'socket_server' => $this->config->get('sockets')['server_uri'] ?: 'ha-socket-io-us-east-1.minds.com:3030',
            'language' => $this->i18n->getLanguage(),
            'languages' => $this->i18n->getLanguages(),
            'categories' => $this->config->get('categories') ?: [],
            'stripe_key' => $this->config->get('payments')['stripe']['public_key'] ?? '',
            'max_video_length' => $this->config->get('max_video_length'),
            'max_video_length_plus' => $this->config->get('max_video_length_plus'),
            'max_video_file_size' => $this->config->get('max_video_file_size'),
            'max_name_length' => $this->config->get('max_name_length') ?? 50,
            'blockchain' => (object)$this->blockchain->getPublicSettings(),
            'last_tos_update' => $this->config->get('last_tos_update') ?: time(),
            'tags' => $this->config->get('tags') ?: [],
            'plus' => $this->config->get('plus'),
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
            'posthog' => [
                ...$this->postHogConfig->getPublicExport(Session::getLoggedinUser()),
                'feature_flags' => Di::_()->get(PostHogService::class)
                    ->getFeatureFlags(user: Session::getLoggedinUser()),
            ],
            'twitter' => [
                'min_followers_for_sync' => $this->config->get('twitter')['min_followers_for_sync'] ?? 25000,
            ],
            'vapid_key' => $this->config->get("webpush_vapid_details")['public_key'],
            'chatwoot' => [
                'website_token' => $this->config->get('chatwoot')['website_token'],
                'base_url' => $this->config->get('chatwoot')['base_url']
            ],
            'strapi' => [
                'url' => $this->config->get('strapi')['url'],
            ],
            'livepeer_api_key' => $this->config->get('livepeer_api_key'),
            'onboarding_v5_release_timestamp' => $this->config->get('onboarding_v5_release_timestamp'),
            'chat' => [
                'unread_count' => Session::getLoggedinUser() ? $this->chatReceiptsService->getAllUnreadMessagesCount(Session::getLoggedinUser()) : 0,
            ],
            'is_tenant' => false, // overridden below.
            'last_cache' => $this->config->get('lastcache') ?? 0,
            // Remove when mobile is read
            'growthbook' => LegacyGrowthBook::getExportedConfigs(Session::getLoggedinUser()),
        ];

        if (Session::isLoggedIn()) {
            /** @var User $user */
            $user = Session::getLoggedinUser();

            $exported['user'] = $user->export();
            $exported['user']['rewards'] = (bool)$user->getPhoneNumberHash();

            if ($user->isPlus()) {
                $exported['max_video_length'] = $this->config->get('max_video_length_plus');
            }

            $canHavePlusTrial = !$user->plus_expires || $user->plus_expires <= strtotime(Wire\Manager::TRIAL_THRESHOLD_DAYS . ' days ago');
            $exported['upgrades']['plus']['monthly']['can_have_trial'] = $canHavePlusTrial;
            $exported['upgrades']['plus']['yearly']['can_have_trial'] = $canHavePlusTrial;

            $exported['permissions'] = array_map(function ($permission) {
                return $permission->name;
            }, $this->rolesService->getUserPermissions($user));
        } else {
            $exported['permissions'] = [];
        }

        if ($context === 'embed') {
            $exported['MindsEmbed'] = null;
        }

        if ($_GET['__e_cnf_token'] ?? false) {
            $exported['from_email_confirmation'] = true;
        }

        // @deprecated
        // tell frontend to redirect. Pro needed this ;/
        $exported['redirect_to_root_on_init'] = false;

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

        if ($tenantId = $this->config->get('tenant_id')) {
            /** @var Tenant */
            $tenant = $this->config->get('tenant');

            $multiTenantConfig = $this->config->get('multi_tenant');

            $exported['is_tenant'] = true;
            $exported['tenant_id'] = $tenantId;

            $exported['tenant'] = [
                'id' => $tenantId,
                'plan' => isset($tenant->plan) ? $tenant->plan->name : TenantPlanEnum::TEAM->name,
                'is_trial' => (bool)$tenant->trialStartTimestamp,
                'trial_length_in_days' => Tenant::TRIAL_LENGTH_IN_DAYS,
                'grace_period_before_deletion_in_days' => Tenant::GRACE_PERIOD_BEFORE_DELETION_IN_DAYS,
                'trial_start' => $tenant->trialStartTimestamp,
                'trial_end' => $tenant->trialStartTimestamp ? strtotime('+' . Tenant::TRIAL_LENGTH_IN_DAYS . ' days', $tenant->trialStartTimestamp) : null,
                'network_deletion_timestamp' => $tenant->trialStartTimestamp ? strtotime('+' . (Tenant::TRIAL_LENGTH_IN_DAYS + Tenant::GRACE_PERIOD_BEFORE_DELETION_IN_DAYS) . ' days', $tenant->trialStartTimestamp) : null,
                'custom_home_page_enabled' => $tenant->config?->customHomePageEnabled ?? false,
                'custom_home_page_description' => $tenant->config?->customHomePageDescription ?? '',
            ];

            $exported['tenant']['max_memberships'] = $multiTenantConfig['plan_memberships'][$exported['tenant']['plan']] ?? 0;
            try {
                $exported['tenant']['total_active_memberships'] = $this->siteMembershipRepository->getTotalSiteMemberships() ?? 0;
            } catch (Exception $e) {
                $exported['tenant']['total_active_memberships'] = 0;
            }

            $exported['theme_override'] = $this->config->get('theme_override');
            $exported['nsfw_enabled'] = $this->config->get('nsfw_enabled') ?? true;
        }

        return $exported;
    }
}
