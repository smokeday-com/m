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

namespace Mirasvit\Affiliate\Block\Adminhtml\Order\View;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\EventRepository;

class Info extends Template
{
    private $registry;

    private $eventRepository;

    private $accountRepository;

    public function __construct(
        Registry $registry,
        EventRepository $eventRepository,
        AccountRepository $accountRepository,
        Template\Context $context,
        array $data = []
    ) {
        $this->registry          = $registry;
        $this->eventRepository   = $eventRepository;
        $this->accountRepository = $accountRepository;

        parent::__construct($context, $data);
    }

    public function getAccount(): ?AccountInterface
    {
        if (!$this->registry->registry('current_order')) {
            return null;
        }

        $collection = $this->eventRepository->getCollection()
            ->addFieldToFilter(
                EventInterface::ENTITY_ID,
                $this->registry->registry('current_order')->getId()
            );

        /** @var EventInterface $event */
        $event = $collection->getFirstItem();

        if (!$event->getAccountId()) {
            return null;
        }

        $account = $this->accountRepository->get($event->getAccountId());

        return $account;
    }
}
