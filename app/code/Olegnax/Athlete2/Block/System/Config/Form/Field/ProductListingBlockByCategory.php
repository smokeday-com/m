<?php

namespace Olegnax\Athlete2\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Backend system config array field renderer for integration test.
 */
class ProductListingBlockByCategory extends AbstractFieldArray {

	/**
	 * Prepare to render
	 *
	 * @return void
	 */
	protected function _prepareToRender() {
		$this->addColumn( 'category_ids', [ 'label' => __( 'Categories' ) ] );
		$this->addColumn( 'block', [ 'label' => __( 'Block' ) ] );
		$this->addColumn( 'sort_order', [ 'label' => __( 'Position' ) ] );
		$this->_addAfter		 = false;
		$this->_addButtonLabel	 = __( 'Add Block' );
	}

}
