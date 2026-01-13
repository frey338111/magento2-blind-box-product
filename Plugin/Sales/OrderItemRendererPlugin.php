<?php
declare(strict_types=1);

namespace Hmh\BlindBoxProduct\Plugin\Sales;

use Hmh\BlindBoxProduct\Service\ProductLookup;
use Magento\Framework\DataObject;
use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;

class OrderItemRendererPlugin
{
    public function __construct(
        private readonly ProductLookup $productLookup
    ) {
    }

    public function afterGetItem(DefaultRenderer $subject, ?DataObject $result): ?DataObject
    {
        if ($result === null) {
            return null;
        }

        $blindBoxSku = (string)$result->getData('blind_box_sku');
        if ($blindBoxSku === '') {
            return $result;
        }
        $result->setSku($blindBoxSku);
        $storeId = $result->getData('store_id');
        $storeId = $storeId !== null ? (int)$storeId : null;
        $name = $this->productLookup->getProductNameBySku($blindBoxSku, $storeId);
        if ($name !== null) {
            $result->setName($name);
        }

        return $result;
    }
}
