<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Backend\Block\Dashboard;

/**
 * Adminhtml dashboard grid
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @api
 * @since 100.0.2
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var string
     */
    protected $_template = 'Magento_Backend::dashboard/grid.phtml';

    /**
     * Setting default for every grid on dashboard
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setDefaultLimit(5);
    }

    /**
     * Prepare page size for 5-row dashboard summary grids without calling setCurPage() (avoids full-table COUNT).
     *
     * @return void
     */
    protected function _preparePage()
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Eliminate redundant COUNT(DISTINCT) full-table scans on Dashboard grids and enable BLOCK_HTML caching on Customers Most grid":
        # https://github.com/smokeday-com/m/issues/17
        $this->getCollection()->setPageSize((int)$this->getParam($this->getVarNameLimit(), $this->_defaultLimit));
    }
}
