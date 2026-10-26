<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Product;

use Magento\Framework\Option\ArrayInterface;

class SwatchWidth implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '',     'label' => __('Default')],
            ['value' => 'stretch',  'label' => __('Stretch swathces width equally')],
            ['value' => 'col',  'label' => __('Columns')],
            ['value' => 'custom',  'label' => __('Custom Size')],
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
