<?php

namespace Olegnax\Athlete2\Model\Config\Settings\Catalog;

use Magento\Framework\Option\ArrayInterface;

class SubcategoryHover implements ArrayInterface {

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
			''		 		 => __( 'None' ),
			'zoom-in'		 => __( 'Image Zoom In' ),
			'zoom-out'		 => __( 'Image Zoom Out' ),
			'zoom-in-item'		 => __( 'Item Zoom In' ),
			'zoom-out-item'		 => __( 'Item Zoom Out' ),
			'move-up'		 => __( 'Item Move Up' ),
			// 'overlay'		 => __( 'Overlay' ),
		];
	}
}
