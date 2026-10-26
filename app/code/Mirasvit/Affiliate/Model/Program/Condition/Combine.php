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

namespace Mirasvit\Affiliate\Model\Program\Condition;

use Magento\Rule\Model\Condition\Context;
use Magento\SalesRule\Model\Rule\Condition\Product as RuleConditionProduct;

class Combine extends \Magento\SalesRule\Model\Rule\Condition\Product\Combine
{
    private const OPTION_GROUP_GENERAL = 'general';
    private const OPTION_GROUP_ACCOUNT = 'account';

    private $generalCondition;

    private $accountCondition;

    public function __construct(
        AccountCondition     $accountCondition,
        GeneralCondition     $generalCondition,
        Context              $context,
        RuleConditionProduct $ruleConditionProduct,
        array                $data = []
    ) {
        parent::__construct($context, $ruleConditionProduct, $data);

        $this->accountCondition = $accountCondition;
        $this->generalCondition = $generalCondition;

        $this->setData('type', self::class);
    }

    public function getNewChildSelectOptions(): array
    {
        $pageAttributes = $this->generalCondition->loadAttributeOptions()->getData('attribute_option');

        $attributes = [];

        foreach ($pageAttributes as $code => $label) {
            $attributes[self::OPTION_GROUP_GENERAL][] = [
                'value' => GeneralCondition::class . '|' . $code,
                'label' => $label,
            ];
        }

        $accountAttributes = $this->accountCondition->loadAttributeOptions()->getData('attribute_option');
        foreach ($accountAttributes as $code => $label) {
            $attributes[self::OPTION_GROUP_ACCOUNT][] = [
                'value' => AccountCondition::class . '|' . $code,
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();

        //replacing SalesRule condition combination with ours
        $conditions[1] = [
            'value' => self::class,
            'label' => __('Conditions Combination'),
        ];

        array_splice($conditions, 2, 0, [
            [
                'label' => __('General'),
                'value' => $attributes[self::OPTION_GROUP_GENERAL],
            ],
            [
                'label' => __('Affiliate Account'),
                'value' => $attributes[self::OPTION_GROUP_ACCOUNT],
            ],
        ]);

        foreach ($conditions as &$condition) {
            /** @var \Magento\Framework\Phrase $label */
            $label = $condition['label'];
            if ($label->getText() == 'Product Attribute') {
                $newProductAttributes = [];
                foreach ($condition['value'] as $attribute) {
                    if (strpos($attribute['value'], 'Product|children') !== false) {
                        continue;
                    }
                    if (strpos($attribute['value'], 'Product|parent') !== false) {
                        continue;
                    }
                    $newProductAttributes[] = $attribute;
                }
                $condition['value'] = $newProductAttributes;
            }
        }
        unset($condition);

        return $conditions;
    }
}
