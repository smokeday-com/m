<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Product;

use Magento\Framework\Option\ArrayInterface;

class ThumbStyle implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '',     'label' => __('Default, Naked')],
            ['value' => 'bordered',  'label' => __('Bordered')],
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
