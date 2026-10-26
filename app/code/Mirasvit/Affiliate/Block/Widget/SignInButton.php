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

namespace Mirasvit\Affiliate\Block\Widget;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Mirasvit\Affiliate\Model\Account;

class SignInButton extends Template implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'Mirasvit_Affiliate::widget/sign_in_button.phtml';

    private $registry;

    public function __construct(
        Registry $registry,
        Template\Context $context,
        array $data
    ) {
        $this->registry = $registry;

        parent::__construct($context, $data);
    }

    public function getAccount(): ?Account
    {
        return $this->registry->registry('current_affiliate') ?: null;
    }
}
