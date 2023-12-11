<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Services;

use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductSubTypeEnum;
use Minds\Core\Payments\Stripe\Checkout\Products\Enums\ProductTypeEnum;
use Minds\Core\Payments\Stripe\Instances\ProductInstance;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Stripe\Product;
use Stripe\SearchResult;

class ProductService
{
    public function __construct(
        private readonly ProductInstance $productInstance
    ) {
    }

    /**
     * @param User $user
     * @param ProductTypeEnum $productType
     * @return SearchResult<Product>
     */
    public function getProductsByType(
        User $user,
        ProductTypeEnum $productType,
        ?ProductSubTypeEnum $productSubType = null
    ): SearchResult {
        $query = "metadata['type']:'$productType->value'";

        if ($productSubType) {
            $query .= " AND metadata['sub_type']:'$productSubType->value'";
        }

        return $this->productInstance
            ->withUser($user)
            ->search([
                'query' => $query,
            ]);
    }

    /**
     * @param User $user
     * @param string $productKey
     * @return Product
     * @throws NotFoundException
     */
    public function getProductByKey(User $user, string $productKey): Product
    {
        /**
         * @var SearchResult<Product> $results
         */
        $results = $this->productInstance
            ->withUser($user)
            ->search([
                'query' => "metadata['key']:'$productKey'",
            ]);

        if ($results->count() === 0) {
            throw new NotFoundException("No product found with key: $productKey");
        }

        return $results->first();
    }
}
