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

namespace Mirasvit\Affiliate\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Mirasvit\Core\Block\Adminhtml\AbstractMenu;

class Menu extends AbstractMenu
{

    public function __construct(
        Context $context
    ) {
        $this->visibleAt(['affiliate']);

        parent::__construct($context);
    }

    protected function buildMenu(): self
    {
        $this->addItem([
            'resource' => 'Mirasvit_Affiliate::affiliate_dashboard',
            'title'    => __('Dashboard'),
            'url'      => $this->urlBuilder->getUrl('affiliate/dashboard'),
        ])->addItem([
            'resource' => 'Mirasvit_Affiliate::affiliate_account',
            'title'    => __('Accounts'),
            'url'      => $this->urlBuilder->getUrl('affiliate/account'),
        ])->addItem([
            'resource' => 'Mirasvit_Affiliate::affiliate_group',
            'title'    => __('Affiliate Groups'),
            'url'      => $this->urlBuilder->getUrl('affiliate/group'),
        ])->addItem([
            'resource' => 'Mirasvit_Affiliate::affiliate_program',
            'title'    => __('Programs'),
            'url'      => $this->urlBuilder->getUrl('affiliate/program'),
        ])->addItem([
            'resource' => 'Mirasvit_Affiliate::affiliate_transaction',
            'title'    => __('Transactions'),
            'url'      => $this->urlBuilder->getUrl('affiliate/transaction'),
        ])->addItem([
            'resource' => 'Mirasvit_Affiliate::affiliate_banner',
            'title'    => __('Banners'),
            'url'      => $this->urlBuilder->getUrl('affiliate/banner'),
        ])->addItem([
            'resource' => 'Mirasvit_Affiliate::affiliate_withdraw',
            'title'    => __('Withdrawals'),
            'url'      => $this->urlBuilder->getUrl('affiliate/withdraw'),
        ])->addItem([
            'resource' => 'Mirasvit_Affiliate::affiliate_referred_customers',
            'title'    => __('Referred Customers'),
            'url'      => $this->urlBuilder->getUrl('affiliate/referred'),
        ]);

        $this->addSeparator();

        $this->addItem([
            'resource' => 'Mirasvit_Affiliate::affiliate_settings',
            'title'    => __('Settings'),
            'url'      => $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/affiliate'),
        ]);

        return $this;
    }
}
