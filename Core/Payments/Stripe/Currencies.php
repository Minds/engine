<?php

namespace Minds\Core\Payments\Stripe;

class Currencies
{
    /**
     * Return the currency by country code
     * @param string $country
     * @return string
     */
    public static function byCountry($country): string
    {
        $countryToCurrency = [
            'AU' => 'AUD',
            'CA' => 'CAD',
            'GB' => 'GBP',
            'HK' => 'HKD',
            'JP' => 'JPY',
            'SG' => 'SGD',
            'US' => 'USD',
            'NZ' => 'NZD',
            'IN' => 'INR',
        ];

        if (!isset($countryToCurrency[$country])) {
            return 'EUR';
        }

        return $countryToCurrency[$country];
    }
}
