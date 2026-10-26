<?php

namespace Olegnax\Athlete2\Block\Adminhtml\Widget\Type;

use Magento\Framework\Stdlib\DateTime;

Class DatePicker extends \Magento\Backend\Block\Template
{
    protected $elementFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }
    
    /**
     * Prepare chooser element HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element Form Element
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function prepareElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $input = $this->elementFactory->create("text", ['data' => $element->getData()]);
        $input->setId($element->getId());
        $input->setForm($element->getForm());
        $input->setClass("widget-option input-date admin__control-text");
        if ($element->getRequired()) {
            $input->addClass('required-entry');
        }

        $element->setData('after_element_html', $input->getElementHtml() . $this->getDatePickerScript($element->getId()));
        $element->setValue(''); // Hides the additional label that gets added.
        return $element;
    }
    /**
     * Get the datepicker script
     *
     * @return string
     */
    protected function getDatePickerScript($id)
    {
        $script = "
            <script>
                require([
                    'jquery',
                    'mage/calendar'
                ], function ($) {
                    $('#" . $id . "').calendar({
                        dateFormat: '". DateTime::DATE_INTERNAL_FORMAT ."',
                        showsTime: true,
                        timeFormat: 'HH:mm:ss',
                        showOn: 'both'
                    });
                });
            </script>
        ";

        return $script;
    }
}