<?php
declare(strict_types=1);

namespace Hmh\BlindBoxProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddBlindBoxProductAttributes implements DataPatchInterface
{
    private const ATTRIBUTE_CODE = 'random_product_pool';
    private const ATTRIBUTE_CODE_IS_BLINDBOX = 'is_blindbox';
    private const GROUP_NAME = 'Blind Box Product';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly EavConfig $eavConfig
    ) {
    }

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityType = $this->eavConfig->getEntityType(Product::ENTITY);
        $entityTypeId = (int)$entityType->getId();
        $eavSetup->addAttribute(
            Product::ENTITY,
            self::ATTRIBUTE_CODE,
            [
                'type'         => 'varchar',
                'label'        => 'Random Product Pool',
                'input'        => 'textarea',
                'required'     => false,
                'user_defined' => true,
                'visible'      => true,
                'global'       => ScopedAttributeInterface::SCOPE_STORE,
                'sort_order'   => 20,
            ]
        );
        $eavSetup->addAttribute(
            Product::ENTITY,
            self::ATTRIBUTE_CODE_IS_BLINDBOX,
            [
                'type'         => 'int',
                'label'        => 'Is Blindbox',
                'input'        => 'boolean',
                'required'     => false,
                'user_defined' => true,
                'visible'      => true,
                'global'       => ScopedAttributeInterface::SCOPE_STORE,
                'default'      => 0,
                'sort_order'   => 10,
            ]
        );

        $attributeSetId = (int)$entityType->getDefaultAttributeSetId();
        $eavSetup->addAttributeGroup($entityTypeId, $attributeSetId, self::GROUP_NAME, 999);
        $groupId = (int)$eavSetup->getAttributeGroupId($entityTypeId, $attributeSetId, self::GROUP_NAME);
        $this->addAttributeToGroup($eavSetup, $entityTypeId, $attributeSetId, $groupId, self::ATTRIBUTE_CODE, 10);
        $this->addAttributeToGroup($eavSetup, $entityTypeId, $attributeSetId, $groupId, self::ATTRIBUTE_CODE_IS_BLINDBOX, 20);
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    private function addAttributeToGroup(
        EavSetup $eavSetup,
        int $entityTypeId,
        int $attributeSetId,
        int $groupId,
        string $attributeCode,
        int $sortOrder
    ): void {
        $attributeId = (int)$eavSetup->getAttributeId($entityTypeId, $attributeCode);
        if (!$attributeId) {
            return;
        }

        $eavSetup->addAttributeToGroup(
            $entityTypeId,
            $attributeSetId,
            $groupId,
            $attributeId,
            $sortOrder
        );
    }
}
