<?php
namespace Olegnax\ProductSlider\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ProductsLayout implements ArrayInterface
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
            'Olegnax_ProductSlider::grid.phtml' => __('Grid, Layout 1 (All Actions Above Image - Centered)'),
            'Olegnax_ProductSlider::gridv2.phtml' => __('Grid, Layout 2 (Quickview Above Image - Bottom Left, Secondary Bottom)'),
			'Olegnax_ProductSlider::gridv3.phtml' => __('Grid, Layout 3 (Add to cart Bottom, Secondary Above Image - Centered)'),
            'Olegnax_ProductSlider::gridv4.phtml' => __('Grid, Layout 4 (Actions Bottom, Always Visible)'),
            'Olegnax_ProductSlider::gridv5.phtml' => __('Grid, Layout 5 (Add to cart Bottom, Secondary Above Image - Top Right)'),
            'Olegnax_ProductSlider::gridv6.phtml' => __('Grid, Layout 6 (All Actions Bottom in 2 columns)'),
            'Olegnax_ProductSlider::gridv7.phtml' => __('Grid, Layout 7 (Add to cart Bottom Stretched, Secondary Above Image - Top Right)'),
            'Olegnax_ProductSlider::carousel.phtml' => __('Carousel, Layout 1 (All Actions Above Image - Centered)'),
            'Olegnax_ProductSlider::carouselv2.phtml' => __('Carousel, Layout 2 (Quickview Above Image - Bottom Left, Secondary Bottom)'),
			'Olegnax_ProductSlider::carouselv3.phtml' => __('Carousel, Layout 3 (Add to cart Bottom, Secondary Above Image - Centered)'),
            'Olegnax_ProductSlider::carouselv4.phtml' => __('Carousel, Layout 4 (Actions Bottom, Always Visible)'),
            'Olegnax_ProductSlider::carouselv5.phtml' => __('Carousel, Layout 5 (Add to cart Bottom, Secondary Above Image - Top Right)'),
            'Olegnax_ProductSlider::carouselv6.phtml' => __('Carousel, Layout 6 (All Actions Bottom in 2 columns)'),
            'Olegnax_ProductSlider::carouselv7.phtml' => __('Carousel, Layout 7 (Add to cart Bottom Stretched, Secondary Above Image - Top Right)'),
            'Olegnax_ProductSlider::list.phtml' => __('List Layouts Simple'),
            'Olegnax_ProductSlider::listv2.phtml' => __('List Layouts, Price and Actions Right'),
            'Olegnax_ProductSlider::listv3.phtml' => __('List Layouts, Actions Right'),
            'Olegnax_ProductSlider::listv4.phtml' => __('List Layouts Long, Price and Actions in separate columns'),
        ];
    }
}