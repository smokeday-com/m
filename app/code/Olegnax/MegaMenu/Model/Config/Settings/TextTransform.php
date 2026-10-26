<?php

namespace Olegnax\MegaMenu\Model\Config\Settings;

use Magento\Framework\Option\ArrayInterface;

class DropdownType implements ArrayInterface {

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
			''		 => __( 'Choose Text Transform...' ),
			'uppercase'		 => __( 'Uppercase' ),
			'capitalize'	 => __( 'Capitalize' ),
			'initial'	 => __( 'Initial' ),
		];
	}

}
