<?php
namespace Olegnax\Athlete2\Model\Config\Settings\StockInfo;

use Magento\Framework\Option\ArrayInterface;

class Position implements ArrayInterface
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
			'above_cart' => __('Above Add to cart'),
			'below_cart' => __('Below Add to cart'),
			'below_actions' => __('Below Actions'),
			'below_price' => __('Below Price'),
            'original' => __('Replace original stock'),
        ];
    }
}
