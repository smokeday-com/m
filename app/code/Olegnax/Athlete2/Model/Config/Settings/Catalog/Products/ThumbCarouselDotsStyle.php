<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Catalog\Products;

use Magento\Framework\Option\ArrayInterface;

class ThumbCarouselDotsStyle implements ArrayInterface
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
            'lines' => __('Theme Default, Lines'),
            'oneline' => __('Short lines without spaces - "Cigarette"'),
			'oneline-fullwidth' => __('Fullwidth lines without spaces - "Cigarette"'),
			'circles' => __('Circles Filled'),
			'circles_outlined' => __('Circles Outlined'),
        ];
    }
}
