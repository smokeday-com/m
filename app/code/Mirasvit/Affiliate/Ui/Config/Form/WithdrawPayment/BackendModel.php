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

namespace Mirasvit\Affiliate\Ui\Config\Form\WithdrawPayment;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Mirasvit\Affiliate\Service\Config\PaymentMethodFieldService;
use Mirasvit\Core\Service\SerializeService;

class BackendModel extends ArraySerialized
{
    public const CODE      = 'code';
    public const LABEL     = 'label';
    public const IS_ACTIVE = 'is_active';

    public const PAYMENT_METHOD_CREDIT = 'credit';

    protected function _afterLoad()
    {
        parent::_afterLoad();

        /** @var array $value */
        $value = $this->getValue();
        if (!is_array($value)) {
            return;
        }

        $fieldService = $this->getFieldService();
        $value        = $fieldService->prepareFieldRows($value);
        $this->setValue($value);
    }

    private function getFieldService(): PaymentMethodFieldService
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Mirasvit\Affiliate\Service\Config\PaymentMethodFieldService');
    }

    public function getOldValue()
    {
        $value = $this->_config->getValue(
            $this->getPath(),
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );

        if (is_array($value)) {
            return (string)SerializeService::encode($value);
        }

        return (string)$value;
    }
}
