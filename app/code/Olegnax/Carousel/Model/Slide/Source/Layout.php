<?php
declare(strict_types=1);

namespace Olegnax\Carousel\Model\Slide\Source;

use Magento\Framework\Option\ArrayInterface;

class Layout implements ArrayInterface
{
    /**
     * @var string
     */
    const LAYOUT1 = 'left';
    const LAYOUT2 = 'right';
	const LAYOUT3 = 'center';
	const LAYOUT4 = '2-col';
	const LAYOUT5 = '2-col -boxed-left';
	const LAYOUT6 = '2-col -boxed-right';
	/**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
			[
				'value' => static::LAYOUT1,
				'label' => __('Left')
			],
			[
				'value' => static::LAYOUT2,
				'label' => __('Right')
			],
			[
				'value' => static::LAYOUT3,
				'label' => __('Center')
			],
			[
				'value' => static::LAYOUT4,
				'label' => __('2 Columns')
			],
			[
				'value' => static::LAYOUT5,
				'label' => __('2 Columns Boxed (Left + Right)')
			],
			[
				'value' => static::LAYOUT6,
				'label' => __('2 Columns Boxed Swapped (Right + Left)')
			],
        ];
    }
}
