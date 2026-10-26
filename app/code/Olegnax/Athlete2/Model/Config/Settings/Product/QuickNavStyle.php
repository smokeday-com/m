<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Product;

use Magento\Framework\Option\ArrayInterface;

class QuickNavStyle implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'default',     'label' => __('Default - Background on hover/active')],
            ['value' => 'minimal',  'label' => __('Minimal - Underline only')],
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
