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

namespace Mirasvit\Affiliate\Ui\Account\Form;

use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Ui\Account\Form\Modifier\CustomerModifier;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\EventRepository;

class DataProvider extends AbstractDataProvider
{
    private $accountRepository;

    private $eventRepository;

    private $urlBuilder;

    private $customerModifier;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AccountRepository $accountRepository,
        EventRepository   $eventRepository,
        UrlInterface      $urlBuilder,
        CustomerModifier  $customerModifier,
        string            $name,
        string            $primaryFieldName,
        string            $requestFieldName,
        array             $meta = [],
        array             $data = []
    ) {
        $this->accountRepository = $accountRepository;
        $this->eventRepository   = $eventRepository;
        $this->urlBuilder        = $urlBuilder;
        $this->customerModifier  = $customerModifier;

        $this->collection = $this->accountRepository->getCollection()->joinCustomer();

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getMeta(): array
    {
        return $this->customerModifier->modifyMeta(parent::getMeta());
    }

    public function getData(): array
    {
        $result = [];

        /** @var AccountInterface|DataObject $item */
        foreach ($this->collection as $item) {
            $itemId = $item->getId();
            $stat   = [];
            $types  = [
                EventInterface::TYPE_IMPRESSION,
                EventInterface::TYPE_CLICK,
                EventInterface::TYPE_LEAD,
                EventInterface::TYPE_SALE,
            ];

            foreach ($types as $type) {
                $collection = $this->eventRepository->getCollection();
                $collection->addFilter(EventInterface::ACCOUNT_ID, $itemId)
                    ->addFilter(EventInterface::TYPE, $type);

                $stat[$type] = $collection->getSize();
            }

            $result[$itemId] = [
                AccountInterface::ID                => $itemId,
                AccountInterface::CUSTOMER_ID       => $item->getCustomerId(),
                AccountInterface::GROUP_ID          => $item->getGroupId(),
                AccountInterface::IDENTIFIER        => $item->getIdentifier(),
                AccountInterface::IS_SUBSCRIBED     => $item->getIsSubscribed() ? '1' : '0',
                AccountInterface::STATUS            => $item->getStatus(),
                AccountInterface::PAYMENT_METHOD    => $item->getPaymentMethod(),
                AccountInterface::PAYMENT_EMAIL     => $item->getPaymentEmail(),
                AccountInterface::BALANCE           => $item->getBalance(),
                AccountInterface::TOTAL_COMMISSIONS => $item->getTotalCommissions(),
                AccountInterface::TOTAL_WITHDRAWALS => $item->getTotalWithdrawals(),
                AccountInterface::CREATED_AT        => $item->getCreatedAt(),
                AccountInterface::PROGRAM_IDS       => $item->getProgramIds(),
                'stat'                              => $stat,
                'customer'                          => [
                    'name'  => $item->getData('name'),
                    'email' => $item->getData('email'),
                    'url'   => $this->urlBuilder->getUrl('customer/index/edit', ['id' => $item->getCustomerId()]),
                ],
            ];

            $additionalData = $item->getAdditionalData();
            foreach ($additionalData as $key => $value) {
                $fieldKey                   = sprintf('%s[%s]', AccountInterface::ADDITIONAL_DATA_SERIALIZED, $key);
                $result[$itemId][$fieldKey] = $value;
            }

            $result = $this->customerModifier->modifyData($result, $itemId);
        }

        return $result;
    }
}
