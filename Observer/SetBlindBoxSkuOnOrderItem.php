<?php
declare(strict_types=1);

namespace Hmh\BlindBoxProduct\Observer;

use Hmh\BlindBoxProduct\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class SetBlindBoxSkuOnOrderItem implements ObserverInterface
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        if (!$order instanceof Order || !$quote instanceof Quote) {
            return;
        }
        $storeId = (int)$order->getStoreId();
        if (!$this->config->isEnabled($storeId)) {
            return;
        }

        $blindBoxSkuByQuoteItemId = [];
        foreach ($quote->getAllItems() as $quoteItem) {
            $blindBoxSku = $quoteItem->getData('blind_box_sku');
            if ($blindBoxSku === null) {
                continue;
            }
            $quoteItemId = (int)$quoteItem->getId();
            if ($quoteItemId > 0) {
                $blindBoxSkuByQuoteItemId[$quoteItemId] = $blindBoxSku;
            }
        }

        if (empty($blindBoxSkuByQuoteItemId)) {
            return;
        }

        foreach ($order->getItems() as $orderItem) {
            $quoteItemId = (int)$orderItem->getQuoteItemId();
            if ($quoteItemId > 0 && array_key_exists($quoteItemId, $blindBoxSkuByQuoteItemId)) {
                $orderItem->setData('blind_box_sku', $blindBoxSkuByQuoteItemId[$quoteItemId]);
            }
        }
    }
}
