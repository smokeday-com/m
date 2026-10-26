<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Product\Bar;

use Magento\Framework\Option\ArrayInterface;

class MobileLayout implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '',     'label' => __('Price Near Add to cart')],
            ['value' => '2',  'label' => __('Price in title column')],
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
