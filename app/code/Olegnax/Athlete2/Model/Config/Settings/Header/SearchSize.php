<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Header;
use Magento\Framework\Option\ArrayInterface;

class SearchSize implements ArrayInterface
{
	public function toOptionArray()
    {
        return [
            ['value' => '',     'label' => __('Default')],
            ['value' => 'big',  'label' => __('Big')],
            ['value' => 'custom',     'label' => __('Custom')],
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
