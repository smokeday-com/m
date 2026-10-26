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

namespace Mirasvit\Affiliate\Ui\Config\Form\Account;

use Magento\Framework\View\Element\Html\Select;

class FieldTypeColumn extends Select
{
    public const FIELD_TYPE_TEXT        = 'text';
    public const FIELD_TYPE_TEXTAREA    = 'textarea';
    public const FIELD_TYPE_CHECKBOX    = 'checkbox';
    public const FIELD_TYPE_CHECKBOXSET = 'checkboxset';
    public const FIELD_TYPE_RADIOSET    = 'radioset';

    public function setInputName($value): FieldTypeColumn
    {
        return $this->setName($value);
    }

    public function setInputId($value): FieldTypeColumn
    {
        return $this->setId($value);
    }

    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    private function getSourceOptions(): array
    {
        return [
            ['label' => 'Text', 'value' => self::FIELD_TYPE_TEXT],
            ['label' => 'Textarea', 'value' => self::FIELD_TYPE_TEXTAREA],
            ['label' => 'Checkbox', 'value' => self::FIELD_TYPE_CHECKBOX],
            ['label' => 'Checkbox Group', 'value' => self::FIELD_TYPE_CHECKBOXSET],
            ['label' => 'Radio Button Group', 'value' => self::FIELD_TYPE_RADIOSET],
        ];
    }
}
