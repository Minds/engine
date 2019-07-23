<?php
/**
 * Stripe Payment Intent
 */
namespace Minds\Core\Payments\Stripe\Intents;

use Minds\Traits\MagicAttributes;

class SetupIntent extends Intent
{
    use MagicAttributes;

    /**
     * Expose to the public apis
     * @param array $extend
     * @return array
     */
    public function export($extend = [])
    {
        return parent::export($extend);
    }

}
