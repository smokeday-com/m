<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mage4\ValidateAddressApi\Plugin\Checkout\Model;

use Magento\Framework\Exception\InputException;

class ShippingInformationManagement
{
    protected $quoteRepository;

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @throws InputException
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement   $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $address = $addressInformation->getShippingAddress();
        if ($address->getVatId()) {
            if (!preg_match('/^[a-zA-Z0-9 ]+$/', $address->getVatId())) {
                throw new InputException(__('Vat Id is incorrect.'));
            }
        }
        if (!preg_match("/^([a-zA-Z' ]+)$/", $address->getFirstname())) {
            throw new InputException(__('First Name format is invalid.'));
        }
        if (!preg_match("/^([a-zA-Z' ]+)$/", $address->getLastname())) {
            throw new InputException(__('Last Name format is invalid.'));
        }
        if (!preg_match('/^[a-zA-Z0-9 ]+$/', $address->getCity())) {
            throw new InputException(__('City format is invalid.'));
        }
    }
}
