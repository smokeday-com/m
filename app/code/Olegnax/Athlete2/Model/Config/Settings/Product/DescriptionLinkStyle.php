<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Product;

use Magento\Framework\Option\ArrayInterface;

class DescriptionLinkStyle implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '',     'label' => __('Simple Link')],
            ['value' => 'button secondary small',     'label' => __('Small Button')],
            ['value' => 'button secondary',     'label' => __('Normal Button')],
            ['value' => 'button secondary bordered naked small rounded',  'label' => __('Small Naked, Rounded, Bordered button')],
            ['value' => 'button naked small',  'label' => __('Small Naked button')],
            ['value' => 'button bordered small',  'label' => __('Small Bordered button')],
            ['value' => 'button underline',  'label' => __('Underlined button')],
            ['value' => 'button outline small',  'label' => __('Outlined button')],
            
        ];
    }
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }
}
