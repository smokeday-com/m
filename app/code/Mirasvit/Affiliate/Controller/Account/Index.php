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

namespace Mirasvit\Affiliate\Controller\Account;

use Magento\Framework\App\Action\Context;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Controller\AbstractAccount;
use Mirasvit\Affiliate\Service\SessionService;

class Index extends AbstractAccount
{
    private $sessionService;

    public function __construct(
        SessionService $sessionService,
        Context $context
    ) {
        $this->sessionService = $sessionService;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $account = $this->sessionService->getAccount();

        if ($account && $account->getStatus() == AccountInterface::STATUS_APPROVED) {
            return $resultRedirect->setPath('*/*/dashboard');
        } else {
            return $resultRedirect->setPath('*/*/welcome');
        }
    }
}
