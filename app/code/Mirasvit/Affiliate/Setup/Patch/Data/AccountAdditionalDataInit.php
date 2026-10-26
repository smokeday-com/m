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


declare(strict_types=1);

namespace Mirasvit\Affiliate\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;

class AccountAdditionalDataInit implements DataPatchInterface
{
    private $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $setup)
    {
        $this->moduleDataSetup = $setup;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): AccountAdditionalDataInit
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $table  = $this->moduleDataSetup->getTable(AccountInterface::TABLE_NAME);
        $select = $connection->select()
            ->from($table, [AccountInterface::ID, AccountInterface::WEBSITE])
            ->where(AccountInterface::WEBSITE . ' IS NOT NULL')
            ->where(AccountInterface::ADDITIONAL_DATA_SERIALIZED . ' IS NULL');

        foreach ($connection->fetchAll($select) as $record) {
            $connection->update(
                $table,
                [
                    AccountInterface::ADDITIONAL_DATA_SERIALIZED => sprintf(
                        '{"%s":"%s"}',
                        AccountInterface::WEBSITE,
                        $record[AccountInterface::WEBSITE]
                    ),
                ],
                [AccountInterface::ID . ' = ?' => $record[AccountInterface::ID]]
            );
        }

        $connection->endSetup();

        return $this;
    }
}
