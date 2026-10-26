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

namespace Mirasvit\Affiliate\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;

class CommissionAssignPrioritySource implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('Assign to first referred affiliate'),
                'value' => ConfigProvider::ASSIGN_PRIORITY_FIRST,
            ],
            [
                'label' => __('Assign to last referred affiliate'),
                'value' => ConfigProvider::ASSIGN_PRIORITY_LAST,
            ],
        ];
    }

    public function toOptionHash(): array
    {
        $options = [];

        foreach ($this->toOptionArray() as $item) {
            $options[$item['value']] = $item['label'];
        }

        return $options;
    }
}
