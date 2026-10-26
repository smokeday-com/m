<?php
namespace Olegnax\Athlete2\Model\Config\Settings;

use Magento\Framework\Option\ArrayInterface;

class Align implements ArrayInterface
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
            '' => __('Default, Left'),
            'center' => __('Center'),
			'right' => __('Right'),
			'left' => __('Force Left'),
        ];
    }
}
