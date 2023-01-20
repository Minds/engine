<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Enums;

class BoostRejectionReason
{
    public const WRONG_AUDIENCE = 1;
    public const AGAINST_MINDS_BOOST_POLICY = 2;
    public const AGAINST_STRIPE_TERMS_OF_SERVICE = 3;
    public const ONCHAIN_PAYMENT_FAILED = 4;

    public const VALID_REJECTION_REASONS = [
        self::WRONG_AUDIENCE,
        self::AGAINST_MINDS_BOOST_POLICY,
        self::AGAINST_STRIPE_TERMS_OF_SERVICE,
        self::ONCHAIN_PAYMENT_FAILED,
    ];

    public static function isValid(?int $rejectionReasonCode): bool
    {
        return in_array($rejectionReasonCode, self::VALID_REJECTION_REASONS, true);
    }

    public static function rejectionReasonsWithLabels(): array
    {
        return [
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
        ];
    }
}
