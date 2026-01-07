<?php
declare(strict_types=1);

namespace Hmh\BlindBoxProduct\Observer;

use Hmh\BlindBoxProduct\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;

class SalesQuoteItemSetProduct implements ObserverInterface
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function execute(Observer $observer): void
    {
        $item = $observer->getEvent()->getQuoteItem();
        if (!$item instanceof Item) {
            return;
        }
        $storeId = (int)$item->getStoreId();
        if (!$this->config->isEnabled($storeId)) {
            return;
        }

        $buyRequest = $item->getBuyRequest();
        $customPrice = $buyRequest->getData('custom_price');
        $originalCustomPrice = $buyRequest->getData('original_custom_price');
        $blindBoxSku = $buyRequest->getData('blind_box_sku');
        if ($customPrice === null && $originalCustomPrice === null) {
            return;
        }

        $price = $customPrice !== null ? (float)$customPrice : (float)$originalCustomPrice;
        if ($price <= 0.0) {
            return;
        }

        $item->setBlindBoxSku($blindBoxSku);
        $item->setCustomPrice($price);
        $item->setOriginalCustomPrice($price);
        $item->getProduct()->setIsSuperMode(true);
    }
}
