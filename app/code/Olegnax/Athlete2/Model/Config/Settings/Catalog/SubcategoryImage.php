<?php

namespace Olegnax\Athlete2\Model\Config\Settings\Catalog;

use Magento\Framework\Option\ArrayInterface;

class SubcategoryImage implements ArrayInterface {

	public function toOptionArray() {
		$optionArray = [ ];
		$array		 = $this->toArray();
		foreach ( $array as $key => $value ) {
			$optionArray[] = [ 'value' => $key, 'label' => $value ];
		}

		return $optionArray;
	}

	public function toArray() {
		return [
			'default'		 => __( 'Default Category Image' ),
			'custom'	 => __( 'Category Thumb (Custom, added by Athlete2 Module)' ),
		];
	}
}
