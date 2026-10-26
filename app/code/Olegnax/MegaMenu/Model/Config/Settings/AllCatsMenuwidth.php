<?php /**/

namespace Olegnax\MegaMenu\Model\Config\Settings;

use Magento\Framework\Option\ArrayInterface;

class AllCatsMenuwidth implements ArrayInterface {

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
			'default' => __('Default'),
			'container' => __('Container Width'), 
			'fullwidth' => __('Full website Width'),
		];
	}

}
