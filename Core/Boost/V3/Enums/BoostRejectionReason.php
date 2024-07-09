<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Enums;

use Minds\Core\Di\Di;

class BoostRejectionReason
{
    public const WRONG_AUDIENCE = 1;
    public const AGAINST_MINDS_BOOST_POLICY = 2;
    public const AGAINST_STRIPE_TERMS_OF_SERVICE = 3;
    public const ONCHAIN_PAYMENT_FAILED = 4;
    public const REPORT_UPHELD = 5;

    /** Reject reasons for non-tenant. */
    public const VALID_REJECTION_REASONS = [
        self::WRONG_AUDIENCE,
        self::AGAINST_MINDS_BOOST_POLICY,
        self::AGAINST_STRIPE_TERMS_OF_SERVICE,
        self::ONCHAIN_PAYMENT_FAILED,
        self::REPORT_UPHELD
    ];

    /** Reject reasons for tenants */
    public const VALID_TENANT_REJECTION_REASONS = [
        self::AGAINST_MINDS_BOOST_POLICY,
        self::AGAINST_STRIPE_TERMS_OF_SERVICE,
        self::REPORT_UPHELD
    ];

    public static function isValid(?int $rejectionReasonCode): bool
    {
        $isTenantNetwork = (bool) Di::_()->get('Config')->get('tenant_id');

        return in_array(
            $rejectionReasonCode,
            !$isTenantNetwork ?
                self::VALID_REJECTION_REASONS :
                self::VALID_TENANT_REJECTION_REASONS,
            true
        );
    }

    public static function rejectionReasonsWithLabels(): array
    {
        $isTenantNetwork = (bool) Di::_()->get('Config')->get('tenant_id');

        return !$isTenantNetwork ? [
            [
                'code' => self::WRONG_AUDIENCE,
                'label' => "Wrong audience"
            ],
            [
                'code' => self::AGAINST_MINDS_BOOST_POLICY,
                'label' => "Against Minds Boost policy"
            ],
            [
                'code' => self::AGAINST_STRIPE_TERMS_OF_SERVICE,
                'label' => "Against Stripe terms of service"
            ],
            [
                'code' => self::ONCHAIN_PAYMENT_FAILED,
                'label' => "Payment failed"
            ],
            [
                'code' => self::REPORT_UPHELD,
                'label' => "Reported"
            ]
        ] : [
            [
                'code' => self::AGAINST_MINDS_BOOST_POLICY,
                'label' => "Against Boost policy"
            ],
            [
                'code' => self::AGAINST_STRIPE_TERMS_OF_SERVICE,
                'label' => "Against Stripe terms of service"
            ],
            [
                'code' => self::REPORT_UPHELD,
                'label' => "Reported"
            ]
        ];
    }
}
