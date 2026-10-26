<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Catalog\Products;

use Magento\Framework\Option\ArrayInterface;

class DescSource implements ArrayInterface
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
            '' => __('Default, short description'),
            'catalog-short' => __('Short Description for Catalog'),
        ];
    }
}
