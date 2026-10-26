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

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;

class CalculationFactory
{
    private $types;

    private $orderRepository;

    public function __construct(
        OrderRepository $orderRepository,
        array           $types = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->types           = $types;
    }

    public function create(AccountInterface $account, EventInterface $event): ?CalculationInterface
    {
        $type    = $event->getType();
        $factory = $this->types[$type] ?? null;
        if (!$factory) {
            return null;
        }

        $params = ['account' => $account, 'event' => $event];
        if ($type == EventInterface::TYPE_SALE) {
            $order = $this->getOrder($event);
            if (!$order) {
                return null;
            }

            $params['order'] = $order;
        }

        return $factory->create($params);
    }

    private function getOrder(EventInterface $event): ?OrderInterface
    {
        try {
            return $this->orderRepository->get($event->getEntityId());
        } catch (\Exception $e) {
            return null;
        }
    }
}
