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

namespace Mirasvit\Affiliate\Plugin\Frontend;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Mirasvit\Affiliate\Service\AccountService;

/**
 * @see \Magento\Framework\App\Response\Http::sendResponse()
 */
class EnsureAccountRelationsBeforeSendResponsePlugin
{
    private $cookieManager;

    private $customerSession;

    private $accountService;

    public function __construct(
        CookieManagerInterface $cookieManager,
        CustomerSession $customerSession,
        AccountService $accountService
    ) {
        $this->cookieManager   = $cookieManager;
        $this->customerSession = $customerSession;
        $this->accountService  = $accountService;
    }

    /**
     * @param mixed $response
     */
    public function beforeSendResponse($response)
    {
        $identifier = $this->cookieManager->getCookie('affiliate_identifier');
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Prevent Mirasvit_Affiliate from starting a PHP session and emitting Set-Cookie: PHPSESSID on every anonymous frontend request":
        # https://github.com/smokeday-com/m/issues/3
        if ($identifier && ($customerId = $this->customerSession->getId())) {
            $this->accountService->ensureCustomerRelation((string)$identifier, (int)$customerId);
        }
    }
}
