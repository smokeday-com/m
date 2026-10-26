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

namespace Mirasvit\Affiliate\Model\Config\Source\Config;

use Magento\Framework\Data\OptionSourceInterface;

class FieldTypeSource implements OptionSourceInterface
{
    public const FIELD_TYPE_TEXT        = 'text';
    public const FIELD_TYPE_TEXTAREA    = 'textarea';
    public const FIELD_TYPE_CHECKBOX    = 'checkbox';
    public const FIELD_TYPE_CHECKBOXSET = 'checkboxes';
    public const FIELD_TYPE_RADIOSET    = 'radios';
    public const FIELD_TYPE_SELECT      = 'select';

    public const TYPES_WITH_PLACEHOLDER
        = [
            self::FIELD_TYPE_TEXT,
            self::FIELD_TYPE_TEXTAREA,
        ];

    public const TYPES_WITH_INPUT
        = [
            self::FIELD_TYPE_TEXT,
            self::FIELD_TYPE_CHECKBOX,
        ];

    public const TYPES_WITH_SET
        = [
            self::FIELD_TYPE_CHECKBOXSET,
            self::FIELD_TYPE_RADIOSET,
        ];

    public const TYPES_WITH_OPTIONS
        = [
            self::FIELD_TYPE_CHECKBOXSET,
            self::FIELD_TYPE_RADIOSET,
            self::FIELD_TYPE_SELECT,
        ];

    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('Text'),
                'value' => self::FIELD_TYPE_TEXT,
            ],
            [
                'label' => __('Textarea'),
                'value' => self::FIELD_TYPE_TEXTAREA,
            ],
            [
                'label' => __('Checkbox'),
                'value' => self::FIELD_TYPE_CHECKBOX,
            ],
            [
                'label' => __('Checkbox Group'),
                'value' => self::FIELD_TYPE_CHECKBOXSET,
            ],
            [
                'label' => __('Radio Button Group'),
                'value' => self::FIELD_TYPE_RADIOSET,
            ],
            [
                'label' => __('Select'),
                'value' => self::FIELD_TYPE_SELECT,
            ],
        ];
    }
}
