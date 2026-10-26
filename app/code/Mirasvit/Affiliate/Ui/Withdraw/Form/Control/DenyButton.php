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

namespace Mirasvit\Affiliate\Ui\Withdraw\Form\Control;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Withdraw\Save as WithdrawSave;

class DenyButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $data = [];
        if ($this->getModel() && $this->getModel()->getStatus() == WithdrawInterface::STATUS_PENDING) {
            $data = [
                'label'          => __('Reject'),
                'class'          => 'reject',
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => 'affiliate_withdraw_form.affiliate_withdraw_form',
                                    'actionName' => 'save',
                                    'params'     => [
                                        true,
                                        [
                                            'action' => WithdrawSave::ACTION_DENY,
                                            'back'   => 'edit',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'sort_order'     => 20,
            ];
        }

        return $data;
    }
}
