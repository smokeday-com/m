<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-affiliate
 * @version   1.5.2
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Affiliate\Setup\Patch\Data;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class DataPatch100 implements DataPatchInterface, PatchVersionInterface
{
    private $setup;

    protected $blockRepository;

    private $blockFactory;

    public function __construct(
        ModuleDataSetupInterface $setup,
        BlockRepositoryInterface $blockRepository,
        BlockInterfaceFactory    $blockFactory
    ) {
        $this->setup           = $setup;
        $this->blockRepository = $blockRepository;
        $this->blockFactory    = $blockFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->setup->getConnection()->startSetup();
        $setup = $this->setup;

        /** @var \Magento\Cms\Api\Data\BlockInterface $block */
        $block = $this->blockFactory->create();

        $block->setIdentifier('affiliate-welcome')
            ->setTitle('Affiliate: Welcome Text')
            ->setContent($this->getBlockContent('welcome'))
            ->setIsActive(true);

        try {
            $this->blockRepository->save($block);
        } catch (\Exception $e) {
        }

        /** @var \Magento\Cms\Api\Data\BlockInterface $block */
        $block = $this->blockFactory->create();

        $block->setIdentifier('affiliate-agreement')
            ->setTitle('Affiliate: Program Agreement')
            ->setContent($this->getBlockContent('agreement'))
            ->setIsActive(true);

        try {
            $this->blockRepository->save($block);
        } catch (\Exception $e) {
        }

        /** @var \Magento\Cms\Api\Data\BlockInterface $block */
        $block = $this->blockFactory->create();

        $block->setIdentifier('affiliate-dashboard')
            ->setTitle('Affiliate: Dashboard')
            ->setContent($this->getBlockContent('dashboard'))
            ->setIsActive(true);

        try {
            $this->blockRepository->save($block);
        } catch (\Exception $e) {
        }

        $this->setup->getConnection()->endSetup();
    }

    protected function getBlockContent(string $name): string
    {
        return file_get_contents(dirname(__FILE__, 3) . "/data/block/$name.html");
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
