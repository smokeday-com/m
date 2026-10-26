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

namespace Mirasvit\Affiliate\Model\ResourceModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\File\Uploader as FileUploader;
use Magento\Framework\Filesystem\Io\File as IoFile;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Mirasvit\Affiliate\Model\ConfigProvider;

class Banner extends AbstractDb
{
    private $configProvider;

    private $ioFile;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    public function __construct(
        ConfigProvider   $configProvider,
        IoFile           $ioFile,
        RequestInterface $request,
        Context          $context
    ) {
        $this->configProvider = $configProvider;
        $this->ioFile         = $ioFile;
        $this->request        = $request;

        parent::__construct($context);
    }

    protected function _construct(): void
    {
        $this->_init('mst_affiliate_banner', 'banner_id');
    }

    protected function _beforeSave(AbstractModel $object): self
    {
        $this->saveImage($object);

        return parent::_beforeSave($object);
    }

    private function saveImage(AbstractModel $banner): self
    {
        $files = $this->request->getFiles();

        if (!isset($files['source']) || !$files['source']['name']) {
            return $this;
        }

        $image = $files['source'];

        $pathInfo = $this->ioFile->getPathInfo($image['name']);
        $ext      = $pathInfo['extension'];
        $name     = $pathInfo['filename'];

        $newFileName = $name . '-' . $banner->getId() . '.' . $ext;

        $allowedFileExtensions = ['png', 'jpeg', 'jpg', 'gif'];
        if (!in_array($ext, $allowedFileExtensions)) {
            throw new ValidatorException(__('File type not allowed (only JPG, JPEG, PNG & GIF files are allowed)'));
        }

        $uploader = new FileUploader($files['source']);
        $uploader->setAllowedExtensions($allowedFileExtensions)
            ->setAllowRenameFiles(false)
            ->setFilesDispersion(false)
            ->setAllowCreateFolders(true)
            ->setAllowRenameFiles(false)
            ->setFilesDispersion(false);
        $uploader->save($this->configProvider->getBaseMediaPath(), $newFileName);

        $banner->setSource($newFileName);

        return $this;
    }
}
