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

use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Template;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Repository\WithdrawRepository;
use Mirasvit\Affiliate\Service\SessionService;

class Withdraws extends Template
{
    private $withdrawRepository;

    private $sessionService;

    private $pricingHelper;

    public function __construct(
        WithdrawRepository $withdrawRepository,
        SessionService     $sessionService,
        PricingHelper      $pricingHelper,
        Template\Context   $context
    ) {
        $this->withdrawRepository = $withdrawRepository;
        $this->sessionService     = $sessionService;
        $this->pricingHelper      = $pricingHelper;

        parent::__construct($context);
    }

    /**
     * @return WithdrawInterface[]
     */
    public function getList(): array
    {
        $account = $this->getAccount();

        if (!$account) {
            return [];
        }

        $withdrawals = $this->withdrawRepository->getCollection()
            ->addFieldToFilter(WithdrawInterface::ACCOUNT_ID, $account->getId())
            ->setOrder(WithdrawInterface::CREATED_AT);

        return $withdrawals->getItems();
    }

    public function getAccount(): ?AccountInterface
    {
        return $this->sessionService->getAccount();
    }

    public function getCurrencyFormatted(float $amount): string
    {
        return (string)$this->pricingHelper->currency($amount, true, false);
    }
}
