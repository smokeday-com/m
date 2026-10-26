<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Catalog\Products;

use Magento\Framework\Option\ArrayInterface;

class QuickviewButtonStyle implements ArrayInterface
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
            '' => __('Default'),
            'outline' => __('Outline'),
            'underline' => __('Underlined'),
            'secondary bordered naked' => __('Bordered'),
            'secondary bordered naked rounded' => __('Bordered rounded'),
            'rounded' => __('Rounded'),
            'naked' => __('Naked'),
        ];
    }
}