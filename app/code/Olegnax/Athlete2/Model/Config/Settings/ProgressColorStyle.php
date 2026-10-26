<?php
namespace Olegnax\Athlete2\Model\Config\Settings;

use Magento\Framework\Option\ArrayInterface;

class ProgressColorStyle implements ArrayInterface
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
			'custom' => __('Custom Color'),
            'ryg' => __('Red-Yellow-Green Gradient'),
        ];
    }
}
