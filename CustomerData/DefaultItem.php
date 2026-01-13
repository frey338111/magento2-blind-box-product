<?php

namespace Hmh\BlindBoxProduct\CustomerData;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Checkout\CustomerData\DefaultItem as CoreDefaultItem;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Hmh\BlindBoxProduct\Service\ProductLookup;
use Magento\Msrp\Helper\Data as MsrpHelper;

class DefaultItem extends CoreDefaultItem
{
    public function __construct(
        Image $imageHelper,
        MsrpHelper $msrpHelper,
        UrlInterface $urlBuilder,
        ConfigurationPool $configurationPool,
        CheckoutHelper $checkoutHelper,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductLookup $productLookup,
        ?Escaper $escaper = null,
        ?ItemResolverInterface $itemResolver = null
    ) {
        parent::__construct(
            $imageHelper,
            $msrpHelper,
            $urlBuilder,
            $configurationPool,
            $checkoutHelper,
            $escaper,
            $itemResolver
        );
    }

    protected function doGetItemData()
    {
        $result = parent::doGetItemData();
        $item = $this->item;
        if (!$item) {
            return $result;
        }

        $blindBoxSku = (string)$item->getData('blind_box_sku');
        if ($blindBoxSku === '') {
            return $result;
        }

        $name = $this->productLookup->getProductNameBySku($blindBoxSku, (int)$item->getStoreId());
        if ($name !== null) {
            $result['product_name'] = $name;
        }

        return $result;
    }

    protected function getProductForThumbnail()
    {
        $blindBoxSku = (string)$this->item->getData('blind_box_sku');
        if ($blindBoxSku === '') {
            return parent::getProductForThumbnail();
        }
        try{
            return $this->productRepository->get($blindBoxSku, false, $this->item->getStoreId(), true);
        }catch (NoSuchEntityException $e) {
            return parent::getProductForThumbnail();
        }
    }

}
