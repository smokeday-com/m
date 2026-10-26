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

namespace Mirasvit\Affiliate\Ui\Account\Form\Fieldset;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Form\FieldFactory;
use Magento\Ui\Component\Form\Fieldset;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\Config\Source\Config\FieldTypeSource;
use Mirasvit\Affiliate\Model\ConfigProvider;

class AdditionalData extends Fieldset
{
    private const FORM_ELEMENTS_BY_TYPE
        = [
            FieldTypeSource::FIELD_TYPE_TEXT        => 'input',
            FieldTypeSource::FIELD_TYPE_TEXTAREA    => 'textarea',
            FieldTypeSource::FIELD_TYPE_CHECKBOX    => 'checkbox',
            FieldTypeSource::FIELD_TYPE_CHECKBOXSET => 'checkboxset',
            FieldTypeSource::FIELD_TYPE_RADIOSET    => 'radioset',
            FieldTypeSource::FIELD_TYPE_SELECT      => 'select',
        ];

    private $fieldFactory;

    private $configProvider;

    public function __construct(
        FieldFactory     $fieldFactory,
        ConfigProvider   $configProvider,
        ContextInterface $context,
        array            $components = [],
        array            $data = []
    ) {
        parent::__construct($context, $components, $data);

        $this->fieldFactory   = $fieldFactory;
        $this->configProvider = $configProvider;
    }

    public function getChildComponents(): array
    {
        foreach ($this->configProvider->getAccountCustomFields(true) as $field) {
            $type = $field[ConfigProvider::FIELD_TYPE] ?? 'text';
            $key  = $field[ConfigProvider::FIELD_KEY] ?? '';
            $name = !empty($key) ? sprintf('%s[%s]', AccountInterface::ADDITIONAL_DATA_SERIALIZED, $key) : '';

            $customField = [
                'label'       => $field[ConfigProvider::FIELD_LABEL] ?? '',
                'formElement' => self::FORM_ELEMENTS_BY_TYPE[$type] ?? 'input',
            ];

            if ($field[ConfigProvider::FIELD_IS_REQUIRED]) {
                $customField['validation'] = ["required-entry" => "true"];
            }

            if (in_array($type, FieldTypeSource::TYPES_WITH_OPTIONS)) {
                if (!count($field[ConfigProvider::FIELD_OPTIONS])) {
                    continue;
                }

                $customField['options'] = [];

                foreach ($field[ConfigProvider::FIELD_OPTIONS] as $option) {
                    $customField['options'][] = [
                        'label' => $option[ConfigProvider::FIELD_OPTION_LABEL] ?? '',
                        'value' => $option[ConfigProvider::FIELD_OPTION_KEY] ?? '',
                    ];
                }
            } else {
                $isCheckbox = FieldTypeSource::FIELD_TYPE_CHECKBOX === $type;

                $customField['dataType'] = $isCheckbox ? 'boolean' : 'text';

                if ($isCheckbox) {
                    $customField['prefer']   = 'toggle';
                    $customField['valueMap'] = [
                        'true' => '1',
                    ];
                }
            }

            $fieldInstance = $this->fieldFactory->create();
            $fieldInstance->setData(
                [
                    'config' => $customField,
                    'name'   => $name,
                ]
            );
            $fieldInstance->prepare();
            $this->addComponent($name, $fieldInstance);
        }


        return parent::getChildComponents();
    }
}
