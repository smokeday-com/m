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

namespace Mirasvit\Affiliate\Program;

use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Api\Data\TierInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Model\Explain\CalculationExplainInterface;
use Mirasvit\Affiliate\Model\Explain\SaleExplainFactory;
use Mirasvit\Affiliate\Program\Sale\ItemValidator;
use Mirasvit\Affiliate\Repository\EventRepository;
use Mirasvit\Affiliate\Service\TierService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Sale implements CalculationInterface
{
    private $configProvider;

    private $eventRepository;

    private $tierService;

    private $pricingHelper;

    private $itemValidator;

    private $resultFactory;

    /**
     * @var CalculationExplainInterface
     */
    private $explain;

    private $explainFactory;

    private $account;

    private $event;

    private $order;

    private $processedOrderItemIds = [];

    private $transactionMessage = '';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ConfigProvider           $configProvider,
        EventRepository          $eventRepository,
        TierService              $tierService,
        PricingHelper            $pricingHelper,
        ItemValidator            $itemValidator,
        CalculationResultFactory $resultFactory,
        SaleExplainFactory       $explainFactory,
        AccountInterface         $account,
        EventInterface           $event,
        OrderInterface           $order
    ) {
        $this->configProvider  = $configProvider;
        $this->eventRepository = $eventRepository;
        $this->tierService     = $tierService;
        $this->pricingHelper   = $pricingHelper;
        $this->itemValidator   = $itemValidator;
        $this->resultFactory   = $resultFactory;
        $this->explainFactory  = $explainFactory;
        $this->account         = $account;
        $this->event           = $event;
        $this->order           = $order;
    }

    public function isSkip(): bool
    {
        return !$this->isNeedAddCommission() && !$this->isNeedRefundCommission();
    }

    public function execute(ProgramInterface $program): CalculationResult
    {
        $this->initTransactionMessage();

        $result        = $this->resultFactory->create();
        $this->explain = $this->explainFactory->create();
        $result->setExplain($this->explain);

        $tier = $this->getTier($program);
        if (!$tier) {
            $this->explain->addMessage('Calculation rule for affiliate group not found');
            $this->explain->setTotal(0.0);

            return $result->setAmount(0.0);
        }

        if ($this->isNeedAddCommission()) {
            $amount = $this->getSaleCommissionAmount($program);
            $this->explain->setTotal($amount);

            return $result->setAmount($amount);
        }

        if ($this->isNeedRefundCommission()) {
            $refundCommissionAmount = $this->getRefundCommissionAmount($program);

            if ($refundCommissionAmount >= 0) {
                $this->explain->setTotal(0.0);

                return $result->setAmount(0.0);
            }
            $this->explain->setTotal($refundCommissionAmount);

            return $result->setAmount($refundCommissionAmount);
        }

        return $result;
    }

    public function getTransactionMessage(): string
    {
        return $this->transactionMessage;
    }

    private function initTransactionMessage(): void
    {
        if ($this->transactionMessage) {
            return;
        }

        if ($this->isNeedAddCommission()) {
            $orderBaseGrandTotal = $this->pricingHelper->currency($this->order->getBaseGrandTotal(), true, false);

            $this->transactionMessage = "Commission for order #{$this->order->getIncrementId()} ($orderBaseGrandTotal)";
        }
        if ($this->isNeedRefundCommission()) {
            $this->transactionMessage = "Refund for order #{$this->order->getIncrementId()}";
        }
    }

    private function getSaleCommissionAmount(ProgramInterface $program): float
    {
        $tier = $this->getTier($program);
        switch ($tier->getType()) {
            case ProgramInterface::COMMISSION_TYPE_PERCENT_TOTAL:
                $this->explain->addMessage('Commission type: Percent of order amount');
                $commissionAmount = $this->getSaleCommissionAmountPercentTotal($program);
                break;

            case ProgramInterface::COMMISSION_TYPE_PERCENT_SUBTOTAL:
                $this->explain->addMessage('Commission type: Percent of cart amount');
                $commissionAmount = $this->getSaleCommissionAmountPercentSubTotal($program);
                break;

            case ProgramInterface::COMMISSION_TYPE_FIXED:
                $this->explain->addMessage('Commission type: Fixed amount');
                $commissionAmount = $this->getSaleCommissionAmountFixed($program);
                break;
            default:
                $this->explain->addMessage('Commission type: unknown');
                $commissionAmount = 0.0;
        }

        return $commissionAmount;
    }

    private function getRefundCommissionAmount(ProgramInterface $program): float
    {
        $tier = $this->getTier($program);
        switch ($tier->getType()) {
            case ProgramInterface::COMMISSION_TYPE_PERCENT_TOTAL:
                $this->explain->addMessage('Commission type: Percent of order amount');
                $refundAmount = $this->getRefundCommissionAmountPercentTotal($program);
                break;

            case ProgramInterface::COMMISSION_TYPE_PERCENT_SUBTOTAL:
                $this->explain->addMessage('Commission type: Fixed amount');
                $refundAmount = $this->getRefundCommissionAmountPercentSubTotal($program);
                break;

            case ProgramInterface::COMMISSION_TYPE_FIXED:
                $this->explain->addMessage('Commission type: Fixed amount');
                $refundAmount = $this->getRefundCommissionAmountFixed($program);
                break;
            default:
                $this->explain->addMessage('Commission type: unknown');
                $refundAmount = 0.0;
        }

        return $refundAmount;
    }

    private function getFilteredValidatedOrderItem(ProgramInterface $program): array
    {
        $validatedOrderItems = $this->itemValidator->getValidatedOrderItems($program->getRule(), $this->order);

        return $this->filterProcessedItems($validatedOrderItems);
    }

    private function getSumAmount(ProgramInterface $program): float
    {
        $orderItems = $this->getFilteredValidatedOrderItem($program);

        $productIds = [];
        foreach ($orderItems as $orderItem) {
            $productIds[] = $orderItem->getProductId();
        }
        $this->explain->addMessage('Processing product ids: ' . implode(', ', $productIds));

        $sum = 0.0;
        foreach ($orderItems as $orderItem) {
            $this->explain->startProduct($orderItem);
            $this->markItemAsProcessed($orderItem);

            $sum += (float)$orderItem->getBaseRowTotal();
            $this->explain->addMessage('+orderItem->baseRowTotal', (float)$orderItem->getBaseRowTotal());

            $sum -= (float)$orderItem->getBaseDiscountAmount();
            $this->explain->addMessage('-orderItem->baseDiscountAmount', -(float)$orderItem->getBaseDiscountAmount());

            if ($this->configProvider->shouldIncludeTax()) {
                $sum += (float)$orderItem->getBaseTaxAmount();
                $this->explain->addMessage('+orderItem->baseTaxAmount', (float)$orderItem->getBaseTaxAmount());
            }
            $this->explain->stopProduct();
        }

        return $sum;
    }

    private function getSaleCommissionAmountPercentTotal(ProgramInterface $program): float
    {
        $sum = $this->getSumAmount($program);
        if (abs($sum) < ConfigProvider::FLOAT_EPSILON) {
            return 0.0;
        }
        $sum += (float)$this->order->getBaseShippingAmount();
        $this->explain->addMessage('+order->baseShippingAmount', (float)$this->order->getBaseShippingAmount());

        if ($this->configProvider->shouldIncludeTax()) {
            $sum += (float)$this->order->getBaseShippingTaxAmount();
            $this->explain->addMessage('+order->baseShippingTaxAmount', (float)$this->order->getBaseShippingTaxAmount());
        }

        $tierAmount = $this->getTierAmount($program);
        $this->explain->addMessage('Commission percent by program settings: ' . $tierAmount . ' %');
        $amount     = $sum * $tierAmount / 100;
        $formulaStr = '(' . $this->explain->getFormulaStr() . ')' . " * $tierAmount / 100 = ";
        $this->explain->setFormulaStr($formulaStr);
        $this->explain->setFormulaResult($amount);

        return $amount;
    }

    private function getSaleCommissionAmountPercentSubTotal(ProgramInterface $program): float
    {
        $sum = $this->getSumAmount($program);
        if (abs($sum) < ConfigProvider::FLOAT_EPSILON) {
            return 0.0;
        }
        $tierAmount = $this->getTierAmount($program);
        $this->explain->addMessage('Commission percent by program settings: ' . $tierAmount . ' %');
        $amount     = $sum * $tierAmount / 100;
        $formulaStr = '(' . $this->explain->getFormulaStr() . ')' . " * $tierAmount / 100 = ";
        $this->explain->setFormulaStr($formulaStr);
        $this->explain->setFormulaResult($amount);

        return $amount;
    }

    private function getCommissionAmountFixed(ProgramInterface $program): float
    {
        $orderItems = $this->getFilteredValidatedOrderItem($program);

        $productIds = [];
        foreach ($orderItems as $orderItem) {
            $productIds[] = $orderItem->getProductId();
        }
        $this->explain->addMessage('Processing product ids: ' . implode(', ', $productIds));

        if (!count($orderItems)) {
            return 0.0;
        }

        foreach ($orderItems as $orderItem) {
            $this->markItemAsProcessed($orderItem);
        }

        $tierAmount = $this->getTierAmount($program);
        $this->explain->addMessage('Commission amount by program settings: ' . $tierAmount);
        $this->explain->setFormulaStr('');
        $this->explain->setFormulaResult($tierAmount);

        return $tierAmount;
    }

    private function getSaleCommissionAmountFixed(ProgramInterface $program): float
    {
        if ($this->order->getBaseTotalRefunded()) {
            $this->explain->addMessage('Refunded found');
            $this->explain->addMessage('Commission: 0');
            $this->explain->setTotal(0.0);

            return 0.0;
        }

        if ($this->order->getBaseTotalCanceled()) {
            $this->explain->addMessage('Canceled found');
            $this->explain->addMessage('Commission: 0');
            $this->explain->setTotal(0.0);

            return 0.0;
        }

        $amount = $this->getCommissionAmountFixed($program);

        return $amount;
    }

    private function getSumRefundAmount(ProgramInterface $program): float
    {
        $orderItems = $this->getFilteredValidatedOrderItem($program);

        $productIds = [];
        foreach ($orderItems as $orderItem) {
            $productIds[] = $orderItem->getProductId();
        }
        $this->explain->addMessage('Processing product ids: ' . implode(', ', $productIds));

        $sum = 0.0;
        foreach ($orderItems as $orderItem) {
            $this->explain->startProduct($orderItem);
            $this->markItemAsProcessed($orderItem);

            $sum -= (float)$orderItem->getBaseAmountRefunded();
            $this->explain->addMessage('-orderItem->baseAmountRefunded', -(float)$orderItem->getBaseAmountRefunded());

            $sum += (float)$orderItem->getBaseDiscountRefunded();
            $this->explain->addMessage('+orderItem->baseDiscountRefunded', (float)$orderItem->getBaseDiscountRefunded());

            if ($this->configProvider->shouldIncludeTax()) {
                $sum -= (float)$orderItem->getBaseTaxRefunded();
                $this->explain->addMessage('-orderItem->baseTaxRefunded', -(float)$orderItem->getBaseTaxRefunded());
            }
            $this->explain->stopProduct();
        }

        return $sum;
    }

    private function getRefundCommissionAmountPercentTotal(ProgramInterface $program): float
    {
        $sum = $this->getSumRefundAmount($program);

        $sum -= (float)$this->order->getBaseShippingRefunded();
        $this->explain->addMessage('-order->baseShippingRefunded', -(float)$this->order->getBaseShippingRefunded());

        if ($this->configProvider->shouldIncludeTax()) {
            $sum -= (float)$this->order->getBaseShippingTaxRefunded();
            $this->explain->addMessage('-order->baseShippingTaxRefunded', -(float)$this->order->getBaseShippingTaxRefunded());
        }

        $tierAmount = $this->getTierAmount($program);
        $this->explain->addMessage('Commission percent by program settings: ' . $tierAmount . ' %');
        $amount     = $sum * $tierAmount / 100;
        $formulaStr = '(' . $this->explain->getFormulaStr() . ')' . " * $tierAmount / 100 = ";
        $this->explain->setFormulaStr($formulaStr);
        $this->explain->setFormulaResult($amount);

        return $amount;
    }

    private function getRefundCommissionAmountPercentSubTotal(ProgramInterface $program): float
    {
        $sum        = $this->getSumRefundAmount($program);
        $tierAmount = $this->getTierAmount($program);
        $this->explain->addMessage('Commission percent by program settings: ' . $tierAmount . ' %');
        $amount     = $sum * $tierAmount / 100;
        $formulaStr = '(' . $this->explain->getFormulaStr() . ')' . " * $tierAmount / 100 = ";
        $this->explain->setFormulaStr($formulaStr);
        $this->explain->setFormulaResult($amount);

        return $amount;
    }

    private function getRefundCommissionAmountFixed(ProgramInterface $program): float
    {
        return $this->getCommissionAmountFixed($program);
    }

    private function getTierAmount(ProgramInterface $program): float
    {
        $eventCollection = $this->eventRepository
            ->getCollection()
            ->addFieldToFilter(EventInterface::ACCOUNT_ID, $this->account->getId())
            ->addFieldToFilter(EventInterface::TYPE, EventInterface::TYPE_SALE)
            ->addFieldToFilter(EventInterface::IS_PROCESSED, true)
            ->addFieldToFilter(EventInterface::CUSTOMER_ID, (int)$this->order->getCustomerId());

        $tier = $this->getTier($program);

        return $this->tierService->getAmount($tier, $eventCollection->getSize());
    }

    /**
     * @param OrderItemInterface[] $orderItems
     *
     * @return OrderItemInterface[]
     */
    private function filterProcessedItems(array $orderItems): array
    {
        return array_filter($orderItems, function ($item) {
            return !in_array($item->getId(), $this->processedOrderItemIds);
        });
    }

    private function markItemAsProcessed(OrderItemInterface $orderItem): void
    {
        $this->processedOrderItemIds[] = $orderItem->getId();
    }

    private function getTier(ProgramInterface $program): ?TierInterface
    {
        $tier  = null;
        $tiers = $program->getTiers();

        foreach ($tiers as $item) {
            if ($this->account->getGroupId() === $item->getGroupId()) {
                $tier = $item;
                break;
            }
        }

        return $tier;
    }

    private function isNeedAddCommission(): bool
    {
        if (in_array($this->order->getStatus(), $this->configProvider->getCommissionAddOrderStatus())) {
            if ($this->event->getCommission() == 0) {
                return true;
            }
        }

        return false;
    }

    private function isNeedRefundCommission(): bool
    {
        if (in_array($this->order->getStatus(), $this->configProvider->getCommissionSubtractOrderStatus())
            || ($this->configProvider->getCommissionSubtractOnCreditmemo() && $this->order->getBaseTotalRefunded() > 0)
        ) {
            if ($this->event->getCommission() > 0) { // commission was added
                return true;
            }
        }

        return false;
    }
}
