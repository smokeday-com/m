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

namespace Mirasvit\Affiliate\Service\Config;

use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Ui\Config\Form\WithdrawPayment\BackendModel;

class PaymentMethodFieldService
{
    private $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function prepareFieldRows(array $rows): array
    {
        $CreditItemKey = $this->getItemKey($rows, BackendModel::PAYMENT_METHOD_CREDIT);
        if ($this->isMirasvitCreditEnabled() && !$CreditItemKey) {
            $rows['item_mst_credit'] = [
                BackendModel::CODE      => BackendModel::PAYMENT_METHOD_CREDIT,
                BackendModel::LABEL     => 'Store Credit',
                BackendModel::IS_ACTIVE => '0',
            ];

            return $rows;
        }
        if (!$this->isMirasvitCreditEnabled() && $CreditItemKey) {
            unset($rows[$CreditItemKey]);

            return $rows;
        }

        return $rows;
    }

    private function getItemKey(array $rows, string $itemCode): ?string
    {
        foreach ($rows as $key => $row) {
            $code = $row[BackendModel::CODE] ?? '';
            if ($code == $itemCode) {
                return $key;
            }
        }

        return null;
    }

    private function isMirasvitCreditEnabled(): bool
    {
        return $this->configProvider->isModuleEnabled('Mirasvit_Credit');
    }
}
