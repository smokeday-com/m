<?php
/**
 * @author      Olegnax
 * @package     Olegnax_Athlete2
 * @copyright   Copyright (c) 2023 Olegnax (http://olegnax.com/). All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Olegnax\Athlete2\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Validator\ValidateException;

class AddOxCategoryOptions implements DataPatchInterface, PatchRevertableInterface
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
    private function getFields()
    {
        return [
            Category::ENTITY => [
                // 'ox_title_text_color' => [
                //     'type' => 'text',
                //     'label' => 'Menu Item Text color',
                //     'input' => 'text',
                //     'required' => false,
                //     'sort_order' => 12,
                //     'wysiwyg_enabled' => false,
                //     'is_html_allowed_on_front' => true,
                //     'global' => ScopedAttributeInterface::SCOPE_STORE,
                //     'group' => 'General Information',
                // ],
				'ox_category_thumb' => [
                    'type' => 'varchar',
                    'label' => 'Category Thumb',
                    'input' => 'image',
                    'required' => false,
                    'sort_order' => 4,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'backend' => 'Magento\Catalog\Model\Category\Attribute\Backend\Image',
                    'group' => 'General Information',
                ],
				'ox_below_toolbar' => [
					'type' => 'text',
					'label' => 'Content Below Toolbar',
					'input' => 'textarea',
					'required' => false,
					'sort_order' => 5,
					'global' => ScopedAttributeInterface::SCOPE_STORE,
					'wysiwyg_enabled' => true,
					'is_html_allowed_on_front' => true,
					'group' => 'General Information',
				],

            ],
        ];
    }

    /**
     * {@inheritdoc}
     * @return AddOxCategoryOptions|void
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
