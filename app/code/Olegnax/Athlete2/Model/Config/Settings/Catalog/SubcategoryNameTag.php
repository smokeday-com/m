<?php

namespace Olegnax\Athlete2\Model\Config\Settings\Catalog;

use Magento\Framework\Option\ArrayInterface;

class SubcategoryNameTag implements ArrayInterface {

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
			'span'		 => __( 'Span' ),
			'strong'		 => __( 'Strong' ),
			'h2'		 => __( 'H2' ),
			'h3'		 => __( 'H3' ),
			'h4'		 => __( 'H4' ),
			'h5'		 => __( 'H5' ),
			'h5'		 => __( 'H6' ),
		];
	}
}
