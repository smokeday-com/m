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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Banner;
use Mirasvit\Affiliate\Model\Config\FileProcessor;
use Mirasvit\Affiliate\Repository\BannerRepository;

class FileUpload extends Banner
{
    private $fileProcessor;

    public function __construct(
        FileProcessor $fileProcessor,
        BannerRepository $bannerRepository,
        Context $context
    ) {
        $this->fileProcessor = $fileProcessor;

        parent::__construct($bannerRepository, $context);
    }

    public function execute(): ResultInterface
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();

        $result = $this->fileProcessor->save('source_image');

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
