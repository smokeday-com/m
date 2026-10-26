<?php

namespace Olegnax\Athlete2\Model\Config\Settings\Header;

use Magento\Framework\Option\ArrayInterface;

class LanguageLabel implements ArrayInterface {

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
			'code' => __( 'Show Language Code' ),
			'name' => __( 'Show Language Full Name' ),
			'custom' => __( 'Custom Text' ),
			'' => __( 'None' ),
		];
	}

}
