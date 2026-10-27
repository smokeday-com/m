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

namespace Mirasvit\Affiliate\Controller\Cookie;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Repository\AccountCustomerRepository;
use Mirasvit\Core\Service\SerializeService;

class Reset extends Action
{
    private $accountCustomerRepository;

    private $configProvider;

    private $customerSession;

    public function __construct(
        AccountCustomerRepository $accountCustomerRepository,
        ConfigProvider            $configProvider,
        CustomerSession           $customerSession,
        Context                   $context
    ) {
        $this->accountCustomerRepository = $accountCustomerRepository;
        $this->configProvider            = $configProvider;
        $this->customerSession           = $customerSession;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Prevent Mirasvit_Affiliate click-abstract.js from sending unconditional POST /affiliate/cookie/reset/ on every page load when no affiliate cookie exists":
        # https://github.com/smokeday-com/m/issues/28
        $request = $this->getRequest();
        $hasAffiliateCookie = method_exists($request, 'getCookie') && (
            (string)$request->getCookie('affiliate', '') !== ''
            || (string)$request->getCookie('affiliate_identifier', '') !== ''
            || (string)$request->getCookie('affiliate_referrer', '') !== ''
        );
        $relationTimestamp  = $hasAffiliateCookie ? $this->getRelationTimestamp() : 0;
        $cookieEndTimestamp = $relationTimestamp ? $relationTimestamp + $this->configProvider->getCookieLifetimeSeconds() : 0;

        $response = $this->getResponse();
        $response->representJson(SerializeService::encode([
            'success'    => true,
            'cookie_end' => $cookieEndTimestamp,
        ]));
    }

    private function getRelationTimestamp(): int
    {
        $customerId = (int)$this->customerSession->getCustomerId();
        if (!$customerId) {
            return 0;
        }
        $relation = $this->accountCustomerRepository->getByCustomerId($customerId);

        return $relation ? (int)strtotime($relation->getCreatedAt()) : 0;
    }
}
