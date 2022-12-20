<?php
namespace Minds\Core\Boost\V3\Insights;

use Minds\Core\Data\MySQL\Client;

class Repository
{
    public function __construct(private ?Client $mysqlClient = null)
    {
        $this->mysqlClient ??= new Client();
    }

    /**
     * Returns the saved estimates
     * @param int $targetAudience
     * @param int $targetLocation
     * @param int $paymentMethod
     * @return array
     */
    public function getEstimate(
        int $targetAudience,
        int $targetLocation,
        int $paymentMethod
    ): array {
        $statement = "SELECT 24h_bids, 24h_views
            FROM boost_estimates
            WHERE target_audience = :target_audience
            AND target_location = :target_location
            AND payment_method = :payment_method
        ";

        $values = [
            'target_audience' => $targetAudience,
            'target_location' => $targetLocation,
            'payment_method' => $paymentMethod,
        ];

        $stmt = $this->mysqlClient->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);
        $stmt->execute($values);

        return $stmt->fetchAll()[0] ?? [
            '24h_bids' => 0,
            '24h_views' => 0
        ];
    }
}
