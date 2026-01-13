<?php
declare(strict_types=1);

namespace Hmh\BlindBoxProduct\Plugin\Checkout;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\AddProductToCart;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Hmh\BlindBoxProduct\Service\ProductLookup;

class AddProductToCartPlugin
{
    public function __construct(
        private readonly ProductResource $productResource,
        private readonly ProductLookup $productLookup
    ) {
    }

    /**
     * Log when a blind box product is added to cart.
     *
     * @param AddProductToCart $subject
     * @param Cart             $cart
     * @param Product          $product
     * @param array            $buyRequest
     * @param array            $related
     *
     * @return array
     */
    public function beforeExecute(
        AddProductToCart $subject,
        Cart $cart,
        Product $product,
        array $buyRequest = [],
        array $related = []
    ): array {
        $originalProduct = $product;
        $productId = (int)$product->getId();
        $storeId = (int)$product->getStoreId();
        if ($this->isBlindBox($productId, $storeId)) {
            $replacement = $this->productLookup->getRandomReplacementProduct($product);
            if ($replacement) {
                $product = $replacement;
                $originalPrice = (float)$originalProduct->getFinalPrice();
                $buyRequest['custom_price'] = $originalPrice;
                $buyRequest['original_custom_price'] = $originalPrice;
                $buyRequest['blind_box_sku'] = $originalProduct->getSku();
                //ensure each blind box product is unique entry in quote item
                $product->addCustomOption(
                    'is_blind_box',
                    uniqid('', true)
                );
            }
        }

        return [
            $cart,
            $product,
            $buyRequest,
            $related,
        ];
    }

    protected function isBlindBox(int $productId, int $storeId): bool
    {
        return (int)$this->productResource->getAttributeRawValue($productId, 'is_blindbox', $storeId) === 1;
    }
}
