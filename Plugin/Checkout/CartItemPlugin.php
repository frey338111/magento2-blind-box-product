<?php
declare(strict_types=1);

namespace Hmh\BlindBoxProduct\Plugin\Checkout;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Block\Cart\Item\Renderer as CartItemRenderer;
use Hmh\BlindBoxProduct\Service\ProductLookup;

class CartItemPlugin
{
    public function __construct(
        private readonly ProductLookup $productLookup
    ) {
    }

    public function afterGetProductName(CartItemRenderer $subject, string $result): string
    {
        $item = $subject->getItem();
        if (!$item) {
            return $result;
        }

        $blindBoxSku = (string)$item->getData('blind_box_sku');
        if ($blindBoxSku === '') {
            return $result;
        }

        return $this->productLookup->getProductNameBySku($blindBoxSku, (int)$item->getStoreId()) ?? $result;
    }

    public function afterGetProductUrl(CartItemRenderer $subject, string $result): string
    {
        $item = $subject->getItem();
        if (!$item) {
            return $result;
        }

        $blindBoxSku = (string)$item->getData('blind_box_sku');
        if ($blindBoxSku === '') {
            return $result;
        }

        $product = $this->productLookup->getProductBySku($blindBoxSku, (int)$item->getStoreId());
        if (!$product) {
            return $result;
        }

        return $product->getUrlModel()->getUrl($product);
    }

    public function afterGetProductForThumbnail(CartItemRenderer $subject, Product $result): Product
    {
        $item = $subject->getItem();
        if (!$item) {
            return $result;
        }

        $blindBoxSku = (string)$item->getData('blind_box_sku');
        if ($blindBoxSku === '') {
            return $result;
        }

        return $this->productLookup->getProductBySku($blindBoxSku, (int)$item->getStoreId()) ?? $result;
    }
}
