<?php

namespace Olegnax\Athlete2\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Backend system config array field renderer for integration test.
 */
class FilterToggleByAttribute extends AbstractFieldArray {

	/**
	 * Prepare to render
	 *
	 * @return void
	 */
	protected function _prepareToRender() {
		$this->addColumn( 'id', [ 'label' => __( 'Attribute ID' ) ] );
		// $this->addColumn( 'state', [ 'label' => __( 'closed state' ) ] );
		$this->_addAfter		 = false;
		$this->_addButtonLabel	 = __( 'Add Attribute' );
	}
}
