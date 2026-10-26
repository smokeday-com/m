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

namespace Mirasvit\Affiliate\Ui\Withdraw\Form\Control;

use Magento\Backend\Block\Widget\Context;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Repository\WithdrawRepository;

class GenericButton
{
    private $withdrawRepository;

    private $context;

    public function __construct(
        WithdrawRepository $withdrawRepository,
        Context $context
    ) {
        $this->withdrawRepository = $withdrawRepository;
        $this->context            = $context;
    }

    public function getId(): ?int
    {
        return $this->context->getRequest()->getParam(WithdrawInterface::ID)
            ? (int)$this->context->getRequest()->getParam(WithdrawInterface::ID)
            : null;
    }

    public function getModel(): ?WithdrawInterface
    {
        if (!$this->getId()) {
            return null;
        }

        return $this->withdrawRepository->get($this->getId());
    }

    public function getUrl(string $route = null, array $params = []): string
    {
        if (!$route) {
            $route = '';
        }

        return (string)$this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
