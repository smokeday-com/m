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

namespace Mirasvit\Affiliate\Model\Group\Condition;

use Magento\Rule\Model\Condition\Context;

class Combine extends \Magento\Rule\Model\Condition\Combine
{
    private $generalCondition;

    public function __construct(
        AffiliateCondition $generalCondition,
        Context            $context,
        array              $data = []
    ) {
        parent::__construct($context, $data);

        $this->generalCondition = $generalCondition;
    }

    public function getNewChildSelectOptions(): array
    {
        $pageAttributes = $this->generalCondition->loadAttributeOptions()->getData('attribute_option');

        $attributes = [];

        foreach ($pageAttributes as $code => $label) {
            $attributes['affiliate'][] = [
                'value' => AffiliateCondition::class . '|' . $code,
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();

        //replacing SalesRule condition combination with ours
        $conditions = array_merge_recursive($conditions, [
            [
                'value' => self::class,
                'label' => __('Conditions Combination'),
            ],
        ]);

        $conditions = array_merge_recursive($conditions, [
            [
                'label' => __('Affiliate conditions'),
                'value' => $attributes['affiliate'],
            ],
        ]);

        return $conditions;
    }
}
