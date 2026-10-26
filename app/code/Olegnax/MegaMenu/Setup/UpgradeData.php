<?php

/**
 * Olegnax MegaMenu
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Olegnax.com license that is
 * available through the world-wide-web at this URL:
 * https://www.olegnax.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Olegnax
 * @package     Olegnax_MegaMenu
 * @copyright   Copyright (c) 2023 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */

namespace Olegnax\MegaMenu\Setup;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Olegnax\MegaMenu\Model\Attribute\MmLvl2AlignVertical;
use Olegnax\MegaMenu\Model\Attribute\CategoryImagePosition;
use Olegnax\MegaMenu\Model\Attribute\MenuIcons;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * Constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory, CategorySetupFactory $categorySetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $this->applyUpgradeFunctions($setup, $context);
        $setup->endSetup();
    }

    private function applyUpgradeFunctions(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $methods = $this->getUpgradeFunctions($context->getVersion());
        foreach ($methods as $method) {
            call_user_func_array([$this, $method], [$setup, $context]);
        }
    }

    private function getUpgradeFunctions($low_version = '0.1.0')
    {
        $methods = get_class_methods($this);
        foreach ($methods as $key => $method) {
            if (preg_match('/^upgrade_(.+)$/i', $method, $matches)) {
                if (version_compare($low_version, $matches[1], '<')) {
                    continue;
                }
            }
            unset($methods[$key]);
        }

        $methods = array_filter($methods);
        $methods = array_unique($methods);
        sort($methods);

        return $methods;
    }

    public function upgrade_0_2_0(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $tableFields = [
            Category::ENTITY => [
                'ox_mm_lvl2_align_vertical' => [
                    'type' => 'text',
                    'label' => 'Mega Menu in Dropdown Behavior',
                    'input' => 'select',
                    'source' => MmLvl2AlignVertical::class,
                    'required' => false,
                    'sort_order' => 71,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Mega menu',
                    'default' => '',
                    'note' => 'For 2st-level category megamenu only'
                ],
            ],
        ];
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        foreach ($tableFields as $entity => $fields) {
            foreach ($fields as $key => $value) {
                if (Category::ENTITY == $entity) {
                    $categorySetup->addAttribute($entity, $key, $value);
                } else {
                    $eavSetup->addAttribute($entity, $key, $value);
                }
            }
        }
    }

    public function upgrade_0_4_0_0(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $tableFields = [
            Category::ENTITY => [
                'ox_mm_exclude_item_from_all_categories' => [
                    'type' => 'int',
                    'label' => 'Exclude from "All Categories" drop down',
                    'input' => 'boolean',
                    'source' => Boolean::class,
                    'required' => false,
                    'sort_order' => 0,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Mega menu',
                    'note' => 'For 1st-level category megamenu only'
                ],
                'ox_mm_show_parent_title' => [
                    'type' => 'int',
                    'label' => 'Show Parent Item Title in Drop Down',
                    'input' => 'boolean',
                    'source' => Boolean::class,
                    'required' => false,
                    'sort_order' => 2,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Mega menu'
                ],
                'ox_nav_link_icon' => [
                    'type' => 'text',
                    'label' => 'Icon near Link',
                    'input' => 'select',
                    'source' => MenuIcons::class,
                    'required' => false,
                    'sort_order' => 23,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Mega menu',
                    'default' => '',
                ],
                'ox_cat_image_pos' => [
                    'type' => 'text',
                    'label' => 'Category Image Position',
                    'input' => 'select',
                    'source' => CategoryImagePosition::class,
                    'required' => false,
                    'sort_order' => 62,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Mega menu',
                    'default' => 'above',
                ],
            ],
        ];
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        foreach ($tableFields as $entity => $fields) {
            foreach ($fields as $key => $value) {
                if (Category::ENTITY == $entity) {
                    $categorySetup->addAttribute($entity, $key, $value);
                } else {
                    $eavSetup->addAttribute($entity, $key, $value);
                }
            }
        }
    }
}
