<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Catalog\Products;

use Magento\Framework\Option\ArrayInterface;

class ThumbCarouselDotsPosition implements ArrayInterface
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
            '' => __('In Image Block'),
            'below' => __('Below Image'),
        ];
    }
}
