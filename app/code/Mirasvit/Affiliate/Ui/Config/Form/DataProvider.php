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

namespace Mirasvit\Affiliate\Ui\Config\Form;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as Source;
use Mirasvit\Affiliate\Model\ConfigProvider;

class DataProvider extends Source
{
    public const DATA_PERSISTOR_KEY = 'affiliate_config_form';

    private $configProvider;

    private $dataPersistor;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ConfigProvider         $configProvider,
        DataPersistorInterface $dataPersistor,
        string                 $name,
        string                 $primaryFieldName,
        string                 $requestFieldName,
        ReportingInterface     $reporting,
        SearchCriteriaBuilder  $searchCriteriaBuilder,
        RequestInterface       $request,
        FilterBuilder          $filterBuilder,
        array                  $meta = [],
        array                  $data = []
    ) {
        $this->configProvider = $configProvider;
        $this->dataPersistor  = $dataPersistor;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    public function getData(): array
    {
        $data = $this->dataPersistor->get(self::DATA_PERSISTOR_KEY);

        if (empty($data)) {
            $data = $this->configProvider->getAccountCustomFields();
        }

        return ['fields' => [ConfigProvider::ACCOUNT_FIELDS => $data]];
    }
}
