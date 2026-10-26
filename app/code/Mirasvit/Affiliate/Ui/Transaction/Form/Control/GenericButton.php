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

namespace Mirasvit\Affiliate\Ui\Transaction\Form\Control;

use Magento\Backend\Block\Widget\Context;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Repository\TransactionRepository;

class GenericButton
{
    private $transactionRepository;

    private $context;

    public function __construct(
        TransactionRepository $transactionRepository,
        Context $context
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->context               = $context;
    }

    public function getId(): ?int
    {
        return $this->context->getRequest()->getParam(TransactionInterface::ID)
            ? (int)$this->context->getRequest()->getParam(TransactionInterface::ID)
            : null;
    }

    public function getModel(): ?TransactionInterface
    {
        return $this->transactionRepository->get($this->getId());
    }

    public function getUrl(string $route = null, array $params = []): string
    {
        if (!$route) {
            $route = '';
        }

        return (string)$this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
