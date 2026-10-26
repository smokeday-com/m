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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Config;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Ui\Config\Form\DataProvider;
use Mirasvit\Core\Service\SerializeService;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Mirasvit_Affiliate::affiliate_settings';

    private $configProvider;

    private $cacheList;

    private $cacheFrontendPool;

    private $dataPersistor;

    public function __construct(
        Context                $context,
        ConfigProvider         $configProvider,
        TypeListInterface      $cacheList,
        Pool                   $cacheFrontendPool,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);

        $this->configProvider    = $configProvider;
        $this->cacheList         = $cacheList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->dataPersistor     = $dataPersistor;
    }

    public function execute(): ResultInterface
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/index/set/fields');
        $data = $this->getRequest()->getPostValue();

        if (!isset($data[ConfigProvider::ACCOUNT_FIELDS]) || !is_array($data[ConfigProvider::ACCOUNT_FIELDS])) {
            return $resultRedirect;
        }

        $data = $this->removeDeletedFields($data[ConfigProvider::ACCOUNT_FIELDS], true);

        $this->dataPersistor->set(DataProvider::DATA_PERSISTOR_KEY, $data);

        try {
            $this->validateKeyUniqueness($data, ConfigProvider::FIELD_KEY);

            foreach ($data as $key => $value) {
                if (isset($value[ConfigProvider::FIELD_OPTIONS]) && is_array($value[ConfigProvider::FIELD_OPTIONS])) {
                    $data[$key][ConfigProvider::FIELD_OPTIONS]
                        = $this->removeDeletedFields($value[ConfigProvider::FIELD_OPTIONS], false);

                    $this->validateKeyUniqueness(
                        $data[$key][ConfigProvider::FIELD_OPTIONS],
                        ConfigProvider::FIELD_OPTION_KEY
                    );
                }
            }

            $this->configProvider->setAccountCustomFields(SerializeService::encode($data));
            $this->cacheList->cleanType(CacheConfig::TYPE_IDENTIFIER);
            $this->cacheFrontendPool->get(CacheConfig::TYPE_IDENTIFIER)->getBackend()->clean();
            $this->messageManager->addSuccessMessage((string)__('Configuration was successfully saved'));
            $this->dataPersistor->clear(DataProvider::DATA_PERSISTOR_KEY);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save: ') . $e->getMessage());
            $this->_getSession()->setFormData($data);
        }

        return $resultRedirect;
    }

    private function removeDeletedFields(array $fields, bool $sort): array
    {
        $cleanedFields = [];
        foreach ($fields as $field) {
            if (isset($field['delete']) && $field['delete']) {
                continue;
            }

            if (!isset($field['position'])) {
                $field['position'] = $field['record_id'] ?? 0;
            }

            $cleanedFields[] = $field;
        }

        if ($sort) {
            usort($cleanedFields, function ($a, $b) {
                return $a['position'] - $b['position'];
            });
        }

        foreach (array_keys($cleanedFields) as $key) {
            $cleanedFields[$key]['record_id'] = $key;
            unset($cleanedFields[$key]['position']);
        }

        return $cleanedFields;
    }

    /**
     * @throws Exception
     */
    private function validateKeyUniqueness(array $data, string $uniqueColumn): void
    {
        $keys = array_column($data, $uniqueColumn);

        if (count($keys) !== count(array_unique($keys))) {
            throw new Exception((string)__('Key values should be unique.'));
        }
    }
}
