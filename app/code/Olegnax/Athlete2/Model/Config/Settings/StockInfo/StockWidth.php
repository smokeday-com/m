<?php
namespace Olegnax\Athlete2\Model\Config\Settings\StockInfo;

use Magento\Framework\Option\ArrayInterface;

class StockWidth implements ArrayInterface
{
	public function toOptionArray() {
		$optionArray = [ ];
		$array		 = $this->toArray();
		foreach ( $array as $key => $value ) {
			$optionArray[] = [ 'value' => $key, 'label' => $value ];
		}

		return $optionArray;
	}

    public function toArray()
    {
        return [
			'auto' => __('Auto'),
			'full' => __('Full Width'),
			'custom' => __('Custom Width'),
        ];
    }
}
