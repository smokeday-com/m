<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-affiliate
 * @version   1.5.2
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Affiliate\Ui\Config\Form\WithdrawPayment;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

class FieldBlock extends AbstractFieldArray
{
    private $activeRenderer;

    protected function _prepareToRender()
    {
        $this->addColumn(BackendModel::CODE, ['label' => __('Code'), 'class' => 'required-entry']);
        $this->addColumn(BackendModel::LABEL, ['label' => __('Label'), 'class' => 'required-entry']);
        $this->addColumn(BackendModel::IS_ACTIVE, [
            'label'    => __('Active'),
            'renderer' => $this->getActiveRenderer(),
        ]);
        $this->_addAfter       = false;
        $this->_addButtonLabel = (string)__('Add');
    }

    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $isActive = $row->getIsActive();
        if ($isActive !== null) {
            $options['option_' . $this->getActiveRenderer()->calcOptionHash($isActive)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    private function getActiveRenderer()
    {
        if (!$this->activeRenderer) {
            $this->activeRenderer = $this->getLayout()->createBlock(
                ActiveColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->activeRenderer;
    }
}
