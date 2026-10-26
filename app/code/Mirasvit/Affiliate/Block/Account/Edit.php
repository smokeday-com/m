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

namespace Mirasvit\Affiliate\Block\Account;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Service\SessionService;

class Edit extends Template
{
    private $configProvider;

    private $sessionService;

    public function __construct(
        ConfigProvider $configProvider,
        SessionService $sessionService,
        Context        $context
    ) {
        $this->configProvider = $configProvider;
        $this->sessionService = $sessionService;

        parent::__construct($context);
    }

    public function getAccount(): ?AccountInterface
    {
        return $this->sessionService->getAccount();
    }

    public function getPaymentMethods(): array
    {
        $methods              = $this->configProvider->getWithdrawPaymentMethodsMap();
        $accountPaymentMethod = $this->getAccountPaymentMethod();
        $paymentMethodList    = array_keys($methods);
        if (!in_array($accountPaymentMethod, $paymentMethodList)) {
            $methods[$accountPaymentMethod] = $accountPaymentMethod;
        }

        return $methods;
    }

    private function getAccountPaymentMethod(): ?string
    {
        $account = $this->getAccount();
        if (!$account) {
            return null;
        }

        return $account->getPaymentMethod();
    }
}
