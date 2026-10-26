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

namespace Mirasvit\Affiliate\Controller\Event;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Mirasvit\Affiliate\Repository\BannerRepository;
use Mirasvit\Affiliate\Service\EventService;

class Click extends Action
{
    private $bannerRepository;

    private $eventService;

    public function __construct(
        BannerRepository $accountRepository,
        EventService     $eventService,
        Context          $context
    ) {
        $this->bannerRepository = $accountRepository;
        $this->eventService     = $eventService;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $bannerId = (int)$this->getRequest()->getParam('banner');

        if ($bannerId) {
            $banner = $this->bannerRepository->get($bannerId);

            $this->eventService->registerClick($banner);
        } else {
            $this->eventService->registerClick(null);
        }
    }
}
