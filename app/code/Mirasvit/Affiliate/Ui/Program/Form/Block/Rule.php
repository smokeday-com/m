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

namespace Mirasvit\Affiliate\Ui\Program\Form\Block;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset as FieldsetRenderer;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions;
use Magento\Rule\Model\Condition\ConditionInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;

class Rule extends Form implements TabInterface
{
    private $fieldsetRenderer;

    private $conditions;

    private $formFactory;

    private $registry;

    private $context;

    /**
     * @var string
     */
    protected $_nameInLayout = 'config.conditions_serialized';

    public function __construct(
        Conditions       $conditions,
        FieldsetRenderer $fieldsetRenderer,
        FormFactory      $formFactory,
        Registry         $registry,
        Context          $context
    ) {
        $this->fieldsetRenderer = $fieldsetRenderer;
        $this->conditions       = $conditions;
        $this->formFactory      = $formFactory;
        $this->registry         = $registry;
        $this->context          = $context;

        parent::__construct($context);
    }

    public function getTabLabel(): string
    {
        return (string)__('Conditions');
    }

    public function getTabTitle(): string
    {
        return (string)__('Conditions');
    }

    public function canShowTab(): bool
    {
        return true;
    }

    public function isHidden(): bool
    {
        return false;
    }

    protected function _prepareForm(): self
    {
        $formName = \Mirasvit\Affiliate\Model\Program\Rule\ProgramRule::FORM_NAME;

        /** @var ProgramInterface $program */
        $program = $this->registry->registry(ProgramInterface::class);

        $rule = $program->getRule();

        $form = $this->formFactory->create();
        $form->setData('html_id_prefix', 'config_');

        $fieldsetName = 'conditions_fieldset';

        $renderer = $this->fieldsetRenderer
            ->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNameInLayout('mst_affiliate_program_condition')
            ->setData(
                'new_child_url',
                $this->getUrl('*/*/newConditionHtml', [
                    'form'      => 'config_' . $fieldsetName,
                    'form_name' => $formName,
                ])
            );

        $fieldset = $form->addFieldset($fieldsetName, [
            'legend' => __('Apply the commission only to cart items matching the following conditions (leave blank for all items).'),

        ])->setRenderer($renderer);

        $rule->getConditions()
            ->setFormName($formName);

        $conditionsField = $fieldset->addField('conditions', 'text', [
            'name'           => 'conditions',
            'required'       => true,
            'data-form-part' => $formName,
        ]);

        $conditionsField->setRule($rule)
            ->setRenderer($this->conditions)
            ->setFormName($formName);

        $form->setValues($program->getData());
        $this->setConditionFormName($rule->getConditions(), $formName);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function setConditionFormName(ConditionInterface $conditions, string $formName): void
    {
        $conditions->setFormName($formName);
        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName);
            }
        }
    }
}
