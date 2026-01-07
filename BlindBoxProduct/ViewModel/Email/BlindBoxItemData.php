<?php
declare(strict_types=1);

namespace Hmh\BlindBoxProduct\ViewModel\Email;

use Hmh\BlindBoxProduct\Service\ProductLookup;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order\Item as OrderItem;

class BlindBoxItemData implements ArgumentInterface
{
    public function __construct(
        private readonly ProductLookup $productLookup
    ) {
    }

    public function getDisplayName(OrderItem $item): string
    {
        $blindBoxSku = (string)$item->getData('blind_box_sku');
        if ($blindBoxSku === '') {
            return (string)$item->getName();
        }

        $storeId = $item->getStoreId();
        $storeId = $storeId !== null ? (int)$storeId : null;
        $name = $this->productLookup->getProductNameBySku($blindBoxSku, $storeId);

        return $name ?? (string)$item->getName();
    }

    public function getDisplaySku(OrderItem $item): string
    {
        $blindBoxSku = (string)$item->getData('blind_box_sku');
        if ($blindBoxSku !== '') {
            return $blindBoxSku;
        }

        $simpleSku = $item->getProductOptionByCode('simple_sku');
        if (is_string($simpleSku) && $simpleSku !== '') {
            return $simpleSku;
        }

        return (string)$item->getSku();
    }
}
