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

namespace Mirasvit\Affiliate\Ui\Banner\Listing;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Mirasvit\Affiliate\Api\Data\BannerInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Service\AnalyticsService;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private $configProvider;

    private $analyticsService;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ConfigProvider $configProvider,
        AnalyticsService $analyticsService,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->configProvider   = $configProvider;
        $this->analyticsService = $analyticsService;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $result = [
            'items'        => [],
            'totalRecords' => $searchResult->getTotalCount(),
        ];

        /** @var BannerInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $data = [
                BannerInterface::ID        => $item->getId(),
                BannerInterface::TITLE     => $item->getTitle(),
                BannerInterface::TYPE      => $item->getType(),
                BannerInterface::IS_ACTIVE => $item->getIsActive() ? '1' : '0',
                BannerInterface::SOURCE    => $item->getIsActive(),
                BannerInterface::LINK      => $item->getLink(),
                BannerInterface::WIDTH     => $item->getWidth(),
                BannerInterface::HEIGHT    => $item->getHeight(),
            ];

            if ($item->getType() == BannerInterface::TYPE_IMAGE) {
                $data['preview_src'] = $this->configProvider->getMediaUrl($item->getSource());
            }

            $stat = [];
            if ($item->getType() == BannerInterface::TYPE_IMAGE) {
                $stat['Clicks']     = $this->analyticsService->getBannerClicks($item);
                $stat['Impression'] = $this->analyticsService->getBannerImpression($item);
            } else {
                $stat['Clicks'] = $this->analyticsService->getBannerClicks($item);
            }

            $html = '<table class="affiliate__grid-table">';
            foreach ($stat as $key => $value) {
                $html .= "<tr><td>$key</td><td>$value</td></tr>";
            }
            $html .= '</table>';

            $data['statistic'] = $html;

            $result['items'][] = $data;
        }

        return $result;
    }
}
