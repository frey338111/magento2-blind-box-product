<?php
declare(strict_types=1);

namespace Hmh\BlindBoxProduct\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductLookup
{
    private array $nameBySku = [];
    private array $productBySku = [];

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductResource $productResource
    ) {
    }

    public function getProductNameBySku(string $sku, ?int $storeId): ?string
    {
        $cacheKey = $this->getCacheKey($sku, $storeId);
        if (array_key_exists($cacheKey, $this->nameBySku)) {
            return $this->nameBySku[$cacheKey];
        }

        $product = $this->getProductBySku($sku, $storeId);
        $this->nameBySku[$cacheKey] = $product ? (string)$product->getName() : null;

        return $this->nameBySku[$cacheKey];
    }

    public function getProductBySku(string $sku, ?int $storeId): ?Product
    {
        $cacheKey = $this->getCacheKey($sku, $storeId);
        if (array_key_exists($cacheKey, $this->productBySku)) {
            return $this->productBySku[$cacheKey];
        }

        try {
            $this->productBySku[$cacheKey] = $this->productRepository->get($sku, false, $storeId, true);
        } catch (NoSuchEntityException $e) {
            $this->productBySku[$cacheKey] = null;
        }

        return $this->productBySku[$cacheKey];
    }

    public function getRandomReplacementProduct(Product $product): ?Product
    {
        $productId = (int)$product->getId();
        $storeId = (int)$product->getStoreId();
        $poolRaw = (string)$this->productResource->getAttributeRawValue(
            $productId,
            'random_product_pool',
            $storeId
        );
        $pool = array_values(array_filter(array_map('trim', explode(',', $poolRaw))));
        if (empty($pool)) {
            return null;
        }

        shuffle($pool);
        foreach ($pool as $sku) {
            $candidate = $this->getProductBySku($sku, $storeId);
            if ($candidate && $candidate->isSalable()) {
                return $candidate;
            }
        }

        return null;
    }

    private function getCacheKey(string $sku, ?int $storeId): string
    {
        $storeKey = $storeId !== null ? (string)$storeId : 'null';

        return $sku . '|' . $storeKey;
    }
}
