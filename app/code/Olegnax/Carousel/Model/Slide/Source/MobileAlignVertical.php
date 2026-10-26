<?php

namespace Olegnax\Carousel\Model\Slide\Source;

use Magento\Framework\Option\ArrayInterface;

class MobileAlignVertical implements ArrayInterface
{

	public function toOptionArray() {
		return [
			[
				'value' => '',
				'label' => __('Center')
			],
			[
				'value' => 'flex-start',
				'label' => __('Top')
			],
			[
				'value' => 'flex-end',
				'label' => __('Bottom')
			],
			[
				'value' => 'space-between',
				'label' => __('Space Between')
			],
			[
				'value' => 'space-evenly',
				'label' => __('Space Evenly')
			],
		];
	}

}
