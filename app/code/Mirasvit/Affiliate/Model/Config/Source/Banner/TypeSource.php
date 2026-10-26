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

namespace Mirasvit\Affiliate\Model\Config\Source\Banner;

use Magento\Framework\Data\OptionSourceInterface;
use Mirasvit\Affiliate\Model\Banner;

class TypeSource implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('Image'),
                'value' => Banner::TYPE_IMAGE,
            ],
            [
                'label' => __('Text'),
                'value' => Banner::TYPE_TEXT,
            ],
        ];
    }
}
