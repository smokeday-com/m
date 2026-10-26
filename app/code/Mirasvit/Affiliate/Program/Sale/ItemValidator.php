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

namespace Mirasvit\Affiliate\Program\Sale;

use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mirasvit\Affiliate\Model\Program\Rule\ProgramRule;
use Mirasvit\Affiliate\Model\SimpleModelFactory;

class ItemValidator
{
    private $quoteRepository;

    private $simpleModelFactory;

    public function __construct(
        QuoteRepository    $quoteRepository,
        SimpleModelFactory $simpleModelFactory
    ) {
        $this->quoteRepository    = $quoteRepository;
        $this->simpleModelFactory = $simpleModelFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param ProgramRule $rule
     * @param OrderInterface $order
     * @return array
     */
    public function getValidatedOrderItems(ProgramRule $rule, OrderInterface $order): array
    {
        $quote = $this->getQuote($order);
        if ($quote) {
            $items = $quote->getAllVisibleItems();
        } else {
            $items = $order->getItems();
        }

        $validatedItems = [];
        foreach ($items as $item) {
            /** @var QuoteItem | OrderItemInterface $item */
            if ($item->getProductType() == 'bundle') {
                $productSku = $item->getProduct()->getData('sku');
                $testItem   = $this->simpleModelFactory->create($item->getData());
                $testItem->setData('sku', $productSku);
                $testItem->setData('product', $item->getProduct());
                if ($rule->validate($testItem)) {
                    $validatedItems[] = $item;
                }
                continue;
            }

            if (!$item->getProduct()) {
                continue;
            }

            if ($rule->validate($item)) {
                $validatedItems[] = $item;
            }
        }

        $orderItems = [];
        foreach ($validatedItems as $item) {
            if ($item instanceof QuoteItem) {
                $orderItem = $this->getOrderItem($order, (int)$item->getId());
                if (!$orderItem) {
                    continue;
                }

                $orderItems[] = $orderItem;
            } else {
                $orderItems[] = $item;
            }
        }

        return $orderItems;
    }

    private function getOrderItem(OrderInterface $order, int $quoteItemId): ?OrderItemInterface
    {
        foreach ($order->getItems() as $item) {
            if ($item->getQuoteItemId() == $quoteItemId) {
                return $item;
            }
        }

        return null;
    }

    private function getQuote(OrderInterface $order): ?\Magento\Quote\Api\Data\CartInterface
    {
        try {
            return $this->quoteRepository->get($order->getQuoteId());
        } catch (\Exception $e) {
            return null;
        }
    }
}
