<?php
/**
 * @author      Olegnax
 * @package     Olegnax_Athlete2
 * @copyright   Copyright (c) 2023 Olegnax (http://olegnax.com/). All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Olegnax\Athlete2\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Validator\ValidateException;

class AddOxCatalogShortDescription implements DataPatchInterface, PatchRevertableInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }

    /**
     * @return \array[][]
     */
    protected function getFields(){
        return [
            Product::ENTITY => [
                'short_description_catalog' =>
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'label' => 'Short Description for Catalog',
                    'input' => 'textarea',
                    'required' => false,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'user_defined' => true,
                    'group' => 'Content',
                    'position' => 10,
                    'sort_order' => 10,
                    'is_searchable' => true,
                    'is_comparable' => true,
                    'used_in_product_listing' => true,
                    'is_visible_in_advanced_search' => true,
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     * @return AddOxGalleryVideoProductAttribute|void
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach ($this->getFields() as $entityTypeId => $fields) {
            foreach ($fields as $code => $attr) {
                $eavSetup->addAttribute($entityTypeId, $code, $attr);
            }
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach ($this->getFields() as $entityTypeId => $fields) {
            foreach ($fields as $code => $attr) {
                $eavSetup->removeAttribute($entityTypeId, $code, $attr);
            }
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
