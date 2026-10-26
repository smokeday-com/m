<?php

namespace Olegnax\Athlete2\Model\Config\Settings\Header;

use Magento\Framework\Option\ArrayInterface;

class HeaderSlideoutPosition implements ArrayInterface {

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
			'' => __( 'Auto' ),
			'right' => __( 'Right' ),
			'left' => __( 'Left' ),
		];
	}

}
