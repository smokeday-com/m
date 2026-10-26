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

use Closure;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Mirasvit\Affiliate\Service\SessionService;

class AuthorizationPlugin
{
    private $customerSession;

    private $sessionService;

    public function __construct(
        CustomerSession $customerSession,
        SessionService $sessionService
    ) {
        $this->customerSession = $customerSession;
        $this->sessionService  = $sessionService;
    }

    /**
     * Dispatch actions allowed for not authorized users
     *
     * @param ActionInterface  $subject
     * @param Closure          $proceed
     * @param RequestInterface $request
     *
     * @return ActionInterface
     */
    public function aroundDispatch(
        ActionInterface $subject,
        Closure $proceed,
        RequestInterface $request
    ) {
        $action = $request->getActionName();
        if (!$this->customerSession->isLoggedIn()) {
            if (!in_array($action, ['welcome', 'create', 'createPost'])) {
                $subject->getActionFlag()->set('', ActionInterface::FLAG_NO_DISPATCH, true);
                $this->customerSession->authenticate();
            }
        } elseif (!$this->sessionService->getAccount()) {
            if (!in_array($action, ['index', 'welcome', 'create', 'createPost'])) {
                $subject->getActionFlag()->set('', ActionInterface::FLAG_NO_DISPATCH, true);
            }
        }

        return $proceed($request);
    }
}
