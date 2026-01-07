<?php
declare(strict_types=1);

namespace Hmh\BlindBoxProduct\Plugin\Checkout;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Checkout\Model\DefaultConfigProvider;
use Hmh\BlindBoxProduct\Service\ProductLookup;

class CheckoutSummaryItemPlugin
{
    public function __construct(
        private readonly ProductLookup $productLookup,
        private readonly ImageHelper $imageHelper
    ) {
    }

    public function afterGetConfig(DefaultConfigProvider $subject, array $result): array
    {
        if (
            empty($result['quoteItemData'])
            || !is_array($result['quoteItemData'])
            || !is_array($result['totalsData'] ?? null)
            || !is_array($result['imageData'] ?? null)
        ) {
            return $result;
        }
        $blindBoxItemInfo = array_reduce(
            $result['quoteItemData'],
            function (array $carry, array $itemData): array {
                $blindBoxSku = $itemData['blind_box_sku'] ?? '';
                if (!$blindBoxSku || empty($itemData['item_id'])) {
                    return $carry;
                }

                $storeId = isset($itemData['store_id']) ? (int)$itemData['store_id'] : null;
                $productReplacement = $this->productLookup->getProductBySku((string)$blindBoxSku, $storeId);
                if (!$productReplacement) {
                    return $carry;
                }

                $itemId = (int)$itemData['item_id'];
                $carry[$itemId]['name'] = $productReplacement->getName();
                $carry[$itemId]['thumbnail'] = $this->imageHelper->init(
                    $productReplacement,
                    'product_thumbnail_image'
                )->getUrl();

                return $carry;
            },
            []
        );

        if (!empty($blindBoxItemInfo)) {
            foreach ($result['totalsData']['items'] as $index => $itemData) {
                $itemId = isset($itemData['item_id']) ? (int)$itemData['item_id'] : null;
                $result['totalsData']['items'][$index]['name'] = $blindBoxItemInfo[$itemId]['name'] ?? $result['totalsData']['items'][$index]['name'];
            }
            foreach ($result['imageData'] as $itemId => $imageData) {
                $result['imageData'][$itemId]['src'] = $blindBoxItemInfo[$itemId]['thumbnail'] ?? $result['imageData'][$itemId]['src'];
                $result['imageData'][$itemId]['alt'] = $blindBoxItemInfo[$itemId]['name'] ?? $result['imageData'][$itemId]['alt'];
            }
        }

        return $result;
    }
}
