<?php
namespace Olegnax\Athlete2\Model\Config\Settings\FreeShipping;
use Magento\Framework\Option\ArrayInterface;

class PositionCartPage implements ArrayInterface
{
	public function toOptionArray()
    {
        return [
            ['value' => 'summary',     'label' => __('In summary block')],
            ['value' => 'top',  'label' => __('Above cart page content/table')],
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
