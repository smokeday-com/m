<?php

namespace Olegnax\Athlete2\Model\Config\Settings\Catalog;

use Magento\Framework\Option\ArrayInterface;

class SubcategoryStyle implements ArrayInterface {

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
			'1'		 => __( 'Boxed, text below' ),
			'5'		 => __( 'Boxed, text left' ),
			'6'		 => __( 'Boxed, text right' ),
			// '2'		 => __( 'Boxed, text overlay' ),
			'3'		 => __( 'Image rounded, text below' ),
			// '4'		 => __( 'Image rounded, text overlay' ),
		];
	}
}
