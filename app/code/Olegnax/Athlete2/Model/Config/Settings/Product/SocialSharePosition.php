<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Product;

use Magento\Framework\Option\ArrayInterface;

class SocialSharePosition implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0,     'label' => __('Below Add to cart')],
            ['value' => 1,  'label' => __('Next to add to cart')],
            ['value' => 'title',  'label' => __('Near Title')],
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
