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

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class DataPatchPaymentMethod implements DataPatchInterface, PatchVersionInterface
{
    private $setup;

    public function __construct(
        ModuleDataSetupInterface $setup
    ) {
        $this->setup = $setup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $defaultMethods = [
            'paypal' => 'PayPal',
            'skrill' => 'Skrill',
            'other'  => 'Other',
        ];

        $this->setup->getConnection()->startSetup();
        $setup = $this->setup;

        $table = $setup->getTable('core_config_data');

        $newConfigRows = $this->setup->getConnection()->fetchAll("SELECT * FROM {$table} WHERE path='affiliate/withdraw/payment_methods_custom'");
        $oldConfigRows = $this->setup->getConnection()->fetchAll("SELECT * FROM {$table} WHERE path='affiliate/withdraw/payment_method'");
        foreach ($oldConfigRows as $oldConfig) {
            if (!$this->isNewConfigExist($oldConfig, $newConfigRows)) {
                $oldConfigMethods = explode(',', $oldConfig['value']);
                $newConfigRows    = [];
                foreach ($defaultMethods as $defaultMethodCode => $defaultMethodLabel) {
                    $newConfigRows['item_' . $defaultMethodCode] = [
                        'code'      => $defaultMethodCode,
                        'label'     => $defaultMethodLabel,
                        'is_active' => in_array($defaultMethodCode, $oldConfigMethods) ? '1' : '0',
                    ];
                }

                foreach ($oldConfigMethods as $oldMethodCode) {
                    if (!in_array($oldMethodCode, array_keys($defaultMethods))) {
                        $newConfigRows['item_' . $oldMethodCode] = [
                            'code'      => $oldMethodCode,
                            'label'     => $oldMethodCode,
                            'is_active' => '1',
                        ];
                    }
                }

                $valueSerialized = \Mirasvit\Core\Service\SerializeService::encode($newConfigRows);
                $scope           = $oldConfig['scope'];
                $scopeId         = (int)$oldConfig['scope_id'];
                $this->setup->getConnection()->insert(
                    $table,
                    ['scope' => $scope, 'scope_id' => $scopeId, 'path' => 'affiliate/withdraw/payment_methods_custom', 'value' => $valueSerialized,]
                );
            }
        }

        $this->setup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion(): string
    {
        return '1.0.14';
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

    private function isNewConfigExist(array $config, array $newConfigRows): bool
    {
        foreach ($newConfigRows as $newConfigRow) {
            if ($config['scope'] == $newConfigRow['scope']
                && $config['scope_id'] == $newConfigRow['scope_id']) {
                return true;
            }
        }

        return false;
    }
}
