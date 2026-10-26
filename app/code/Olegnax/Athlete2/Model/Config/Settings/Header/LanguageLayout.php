<?php

namespace Olegnax\Athlete2\Model\Config\Settings\Header;

use Magento\Framework\Option\ArrayInterface;

class LanguageLayout implements ArrayInterface {

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
			'simple' => __( 'Simple list' ),
			'2cols' => __( '2 columns blocks' ),
			'adaptive' => __( 'Adaptive columns for block items' ),
			// 'select' => __( 'Items Select' ),
		];
	}

}
