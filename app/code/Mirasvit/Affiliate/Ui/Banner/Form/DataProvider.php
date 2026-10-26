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

namespace Mirasvit\Affiliate\Ui\Banner\Form;

use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Mirasvit\Affiliate\Api\Data\BannerInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Repository\BannerRepository;

class DataProvider extends AbstractDataProvider
{
    private $bannerRepository;

    private $configProvider;

    private $driverFile;

    public function __construct(
        BannerRepository $transactionRepository,
        ConfigProvider   $configProvider,
        DriverFile       $driverFile,
        string           $name,
        string           $primaryFieldName,
        string           $requestFieldName,
        array            $meta = [],
        array            $data = []
    ) {
        $this->bannerRepository = $transactionRepository;
        $this->collection       = $this->bannerRepository->getCollection();
        $this->configProvider   = $configProvider;
        $this->driverFile       = $driverFile;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        $result = [];

        foreach ($this->collection as $item) {
            $result[$item->getId()] = [
                BannerInterface::ID        => $item->getId(),
                BannerInterface::TITLE     => $item->getTitle(),
                BannerInterface::TYPE      => $item->getType(),
                BannerInterface::IS_ACTIVE => $item->getIsActive() ? '1' : '0',
                BannerInterface::SOURCE    => $item->getSource(),
                BannerInterface::LINK      => $item->getLink(),
                BannerInterface::WIDTH     => $item->getWidth(),
                BannerInterface::HEIGHT    => $item->getHeight(),
                BannerInterface::STORE_IDS => $item->getStoreIds(),
                'source_text'              => $item->getSource(),
            ];

            if ($item->getType() == BannerInterface::TYPE_IMAGE) {
                $fileStat = $this->driverFile->stat($this->configProvider->getMediaPath($item->getSource()));

                $result[$item->getId()]['source_image'] = [
                    0 => [
                        'name' => $item->getSource(),
                        'url'  => $this->configProvider->getMediaUrl($item->getSource()),
                        'size' => $fileStat['size'],
                        'type' => 'image',
                    ],
                ];
            }
        }

        return $result;
    }
}
