<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Product;

use Magento\Framework\Option\ArrayInterface;

class QuickNavPosition implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '',     'label' => __('Before All')],
            ['value' => 'after-info',  'label' => __('After Product Info')],
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
