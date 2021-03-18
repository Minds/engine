<?php
/**
 * Stripe PaymentMethod
 */
namespace Minds\Core\Payments\Stripe\PaymentMethods;

use Minds\Traits\MagicAttributes;
use Minds\Entities\User;

/**
 * @method PaymentMethod getId(): string
 * @method PaymentMethod getCustomerId(): string
 * @method PaymentMethod getUserGuid(): string
 * @method PaymentMethod getCardBrand(): string
 * @method PaymentMethod getCardExpires(): string
 * @method PaymentMethod getCardCountry(): string
 * @method PaymentMethod getCardLast4(): string
 */
class PaymentMethod
{
    use MagicAttributes;

    /** @var string $id */
    private $id;

    /** @var string $customerId */
    private $customerId;

    /** @var string $userGuid */
    private $userGuid;

    /** @var string $cardBrand */
    private $cardBrand;

    /** @var string $cardExpires */
    private $cardExpires;

    /** @var string $cardCountry */
    private $cardCountry;

    /** @var int $cardLast4 */
    private $cardLast4;

    /** @var User */
    private $user;

    /**
     * Expose to the public apis
     * @param array $extend
     * @return array
     */
    public function export(array $extend = []) : array
    {
        return [
            'id' => $this->id,
            'user_guid' => (string) $this->userGuid,
            'user' => $this->user ? $this->user->export() : null,
            'card_brand' => $this->cardBrand,
            'card_country' => $this->cardCountry,
            'card_expires' => $this->cardExpires,
            'card_last4' => $this->cardLast4,
        ];
    }
}
