<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Models;

/**
 * Custom field for Stripe Checkout.
 */
class CustomField
{
    /** Key of the field. */
    private string $key;

    /** Label text of the field. */
    private string $label;

    /** Type of the field. */
    private string $type;

    /** Whether the field is optional. */
    private bool $optional;

    public function __construct(
        string $key,
        string $label,
        string $type,
        bool $optional = false,
    ) {
        $this->key = $key;
        $this->label = $label;
        $this->type = $type;
        $this->optional = $optional;
    }

    /**
     * Export class values as array for ingestion into Stripe Checkout.
     * @return array - array for the custom field to be ingested into Stripe Checkout.
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => [
                'type' => 'custom',
                'custom' => $this->label,
            ],
            'optional' => $this->optional,
            'type' => $this->type
        ];
    }
}