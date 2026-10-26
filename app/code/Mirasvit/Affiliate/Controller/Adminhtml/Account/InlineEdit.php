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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Account;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Mirasvit\Affiliate\Api\Data\CouponInterface;
use Mirasvit\Affiliate\Repository\CouponRepository;
use Mirasvit\Affiliate\Service\CouponService;

class InlineEdit extends Action
{
    private $jsonFactory;

    private $couponRepository;

    private $couponService;

    public function __construct(
        Context          $context,
        JsonFactory      $jsonFactory,
        CouponRepository $couponRepository,
        CouponService    $couponService
    ) {
        parent::__construct($context);
        $this->jsonFactory      = $jsonFactory;
        $this->couponRepository = $couponRepository;
        $this->couponService    = $couponService;
    }

    public function execute(): ResultInterface
    {
        $resultJson = $this->jsonFactory->create();
        $error      = false;
        $messages   = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);

            if (!count($postItems)) {
                $messages[] = (string)__('Please correct the data sent.');
                $error      = true;
            } else {
                foreach (array_keys($postItems) as $couponId) {
                    $couponCode = trim($postItems[$couponId]['code']);

                    if (!$couponCode) {
                        $messages[] = (string)__('Coupon code can\'t be empty');
                        $error      = true;

                        continue;
                    }

                    try {
                        /** @var CouponInterface $coupon */
                        $coupon = $this->couponRepository->getCollection()
                            ->addFieldToFilter(CouponInterface::ID, $couponId)
                            ->getFirstItem();
                        $this->couponService->updateCouponCode($coupon, $couponCode);
                    } catch (\Exception $e) {
                        $messages[] = "[Error:]  {$e->getMessage()}";
                        $error      = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error'    => $error,
        ]);
    }
}
