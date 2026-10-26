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

namespace Mirasvit\Affiliate\Model\Config\Source\Program;

use Magento\Framework\Data\OptionSourceInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;

class CommissionTypeSource implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('Percent of order amount'),
                'value' => ProgramInterface::COMMISSION_TYPE_PERCENT_TOTAL,
            ],
            [
                'label' => __('Percent of cart amount'),
                'value' => ProgramInterface::COMMISSION_TYPE_PERCENT_SUBTOTAL,
            ],
            [
                'label' => __('Fixed amount'),
                'value' => ProgramInterface::COMMISSION_TYPE_FIXED,
            ],
        ];
    }
}
